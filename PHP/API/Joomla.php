<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');
jimport('joomla.application.component.helper');

/**
 * LuxorTicket View
 */
class LuxorTicketViewLuxorTickets extends JViewLegacy
{
	/**
	 * LuxorTicket view display method
	 */
	function display($tpl = null)
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$curdate = date('d-m-Y');
		$this->document->addStyleSheet('/components/com_luxorticket/assets/css/luxorticket.css');

		$clientip = $_SERVER['REMOTE_ADDR'];

		// Get component params
		$params = JComponentHelper::getParams('com_luxorticket');

		$apikey 		= $params->get('apikey', null);

		$cachetime 		= $params->get('cachetime', null);

		$luxorsite		= $params->get('luxorsite', null);

		$noposterimg 	= $params->get('noposterimg', null);
		$this->noposterimg = file_exists(JPATH_ROOT.'/images/'.$noposterimg) ? '/images/'.$noposterimg : '';

		// Get site events from API
		$from = $input->get('from', null, 'STR');
		$from = !empty($from) ? $from : $curdate;

		$to = date("d-m-Y", strtotime("{$from} +1 week"));
		$to_ = date("d-m-Y", strtotime("{$from} +6 day"));

		$this->href_actual = "/index.php?option=com_luxorticket";
		$this->href_next = "/index.php?option=com_luxorticket&from={$to}";
		$this->from = $from;
		$this->to_ = $to_;

		$this->events = array();
		$events = $this->getArrayFromXmlPath("http://ticketing.luxor-kino.de:20444/1.0/events?apikey={$apikey}&clientip={$clientip}&siteid={$luxorsite}&from={$from}&to={$to}");
		$this->events = isset($events['Movie'][0]) ? $events['Movie'] : $events;

		// Apply cache. Cache will be refreshed: if list of events was changede, or cache time is expired.
		$cache = JFactory::getCache('LuxorEvents', '');
		$cache->setCaching(true);
		$cache->setLifeTime($cachetime);  // in minutes
		$cache_id = base64_encode(serialize($this->events));
		$cached_page = $cache->get($cache_id);

		if (!empty($cached_page)) 
		{
		    $the_page_output = $cached_page;
		}
		else 
		{	// Slow part of code - many api requests.
			foreach ($this->events as $key => &$event) {

				// Get event img
				$event_images = $this->getArrayFromXmlPath(
					"http://ticketing.luxor-kino.de:20444/1.0/events/{$event['EventID']}/images?apikey={$apikey}&clientip={$clientip}&siteid={$luxorsite}"
				);

				if(!empty($event_images))
				{
					$first_image_id = $event_images['Image']['@attributes']['ID'];
					$first_image = $this->getArrayFromXmlPath(
						"http://ticketing.luxor-kino.de:20444/1.0/events/{$event['EventID']}/images/{$first_image_id}?apikey={$apikey}&clientip={$clientip}&siteid={$luxorsite}"
					);
					$event['image'] = $first_image[0];
				}
				else 
				{
					$event['image'] = false;
				}

				// Get event shows
				$shows = $this->getArrayFromXmlPath(
					"http://ticketing.luxor-kino.de:20444/1.0/shows?apikey={$apikey}&clientip={$clientip}&siteid={$luxorsite}&eventid={$event['EventID']}&from={$from}&to={$to_}"
				);
				$shows = isset($shows['Show'][0]) ? $shows['Show'] : $shows;

				// Get shows array in comfort format
				$event['shows'] = $this->getShowsArr($shows);
			}


		    $the_page_output = $this->loadTemplate($tpl);
		    $cache->store($the_page_output, $cache_id);
		}

		// Display the template
		echo $the_page_output;
	}

	private function getArrayFromXmlPath($path)
	{
		$xml_str = file_get_contents($path);
		$xml_obj = simplexml_load_string($xml_str);
		$xml_arr = json_decode(json_encode($xml_obj), TRUE);

		return $xml_arr;
	}

	private function getShowsArr($shows)
	{
		$res = array();
		$rows = 0;
		foreach ($shows as $show) {			
			$is_added = false;
			for( $i = 0; $i <= $rows; $i++) {

				if(!isset($res[$i][$show['AnnouncementDate']])) {
					$res[$i][$show['AnnouncementDate']]['ShowTime'] = date('H:i', strtotime($show['ShowTime']));
					$res[$i][$show['AnnouncementDate']]['AuditoriumName'] = $show['AuditoriumName'];
					$res[$i][$show['AnnouncementDate']]['ID'] = $show['@attributes']['ID'];
					$is_added = true;
					break;
				}
			}

			if(!$is_added){
				$rows++;
				$res[$rows][$show['AnnouncementDate']]['ShowTime'] = date('H:i', strtotime($show['ShowTime']));
				$res[$rows][$show['AnnouncementDate']]['AuditoriumName'] = $show['AuditoriumName'];
				$res[$rows][$show['AnnouncementDate']]['ID'] = $show['@attributes']['ID'];
			}
		}

		return $res;
	}
}