<?php

/**
 * Class Vendors
 */
class Vendors extends Controller
{
    private $admin_id = null;
    private $access = false;

    public function __construct ()
    {
        $this->admin_id = UserHelper::checkUser ();

        UserRoleHelper::PageAccess ($this->admin_id, 'VendorsEdit');
    }

    public function index ()
    {
        // Get vendors by id
        $vendorsList = Model::load ("Admin/VendorsModel")->getVendors ();

        $vendorsList_table = new View ("Admin/Layers/VendorsList");
        $vendorsList_table->setArray (array (
            "vendorsList" => $vendorsList
        ));

        // Page's template
        $template = new View ("Admin/Template/Index");

        // Page's content
        $content = new View ("Admin/Content/Vendors");
        $content->setArray (array (
            "vendorsTable" => $vendorsList_table->render (false)
        ));

        // Set view in template
        $template->setArray (array (
            "content" => $content
        ));

        $template->render ();
    }

    public function AddNew ()
    {
        $name = isset ($_POST['name']) ? Charset::CleanStr ($_POST['name']) : null;

        if ($name == null)
        {
            echo json_encode (array (
                'error' => true,
                'message' => 'Enter vendor\'s name'
            ));
            return;
        }

        $vendor_id = Model::load ("Admin/VendorsModel")->Create ($name);

        $data = array (
            "vendor_id" => $vendor_id,
            "from_price" => 0,
            "to_price" => 0,
            "add_mult" => 0,
            "add_amount" => 0,
            "disc_percent" => 0,
            "disc_amount" => 0
        );
        $this->AddPriceRange ($data);

        $dataShip = array (
            "vendor_id" => $vendor_id,
            "shipping_id" => 0,
            "price" => 0,
            "days" => 0
        );
        $this->AddShipping ($dataShip);
//
        // Get vendors by id
        $vendorsList = Model::load ("Admin/VendorsModel")->getVendors ();

        $vendorsList_table = new View ("Admin/Layers/VendorsList");
        $vendorsList_table->setArray (array (
            "vendorsList" => $vendorsList
        ));

        echo json_encode (array (
            'success' => true,
            'html' => $vendorsList_table->render (false)
        ));
    }

    public function GetVendor ()
    {
        $vendor_id = (int) $_POST["id"];

        $only_read = (isset ($_POST['onlyread']) && $_POST['onlyread'] == 'true') ? true : false;

        if ($vendor_id)
        {
            // Get vendors
            $vendorsList = Model::load ("Admin/VendorsModel")->getVendors ();
            // Get vendor by id
            $vendor = array ();
            foreach ($vendorsList as $key => $value)
            {
                if ($value["Id"] == $vendor_id)
                {
                    $vendor = $value;
                    break;
                }
            }

            // Get prices
            $priceInfo = Model::load ("Admin/VendorsModel")->getPriceLogic ($vendor_id);

            // Get Shipping by vendor
            $vendorsShipping = Model::load ("Admin/ShippingModel")->getShippingByVendor ($vendor_id);

            // Get all shipping
            $shipping = Model::load ("Admin/ShippingModel")->getShipping ();
            
            $prodCategories = Model::load ("Admin/VendorsModel")->getCategoriesByVendorId ($vendor_id);

            $vendorInfo_html = new View ("Admin/Layers/VendorInfo");
            $vendorInfo_html->setArray (array (
                "vendor" => $vendor,
                "price" => $priceInfo,
                'only_read' => $only_read,
                'vendorsShipping' => $vendorsShipping,
                'shipping' => $shipping,
                'prodCategories' => $prodCategories
            ));

            echo json_encode (array (
                "content" => $vendorInfo_html->render (false),
                "success" => true,
                "id" => $vendor_id
            ));
            return;
        }

        echo json_encode (array (
            "content" => "Vendor not found",
            "id" => $vendor_id
        ));
    }

    public function RemovePriceRange ()
    {
        $record_price_range_id = (int) $_POST["id"];

        Model::load ("Admin/VendorsModel")->removePriceLogicRecord ($record_price_range_id);


        echo json_encode (array (
            "id" => $record_price_range_id
        ));
    }

    public function RemoveShipping ()
    {
        $vendor_id = (int) $_POST["vendor_id"];
        $shipping_id = (int) $_POST["id"];

        Model::load ("Admin/VendorsModel")->removeShippingRecord ($vendor_id, $shipping_id);


        echo json_encode (array (
            "id" => $shipping_id
        ));
    }

    public function AddShipping ($data = null)
    {
        if ($data == null):
            $vendor_id = (int) $_POST["vendor_id"];
            $shipping_id = (int) $_POST["shipping_id"];
            $price = Charset::CleanStr ($_POST["price"]);
            $days = Charset::CleanStr ($_POST["days"]);
            $name = '';
        else:
            $vendor_id = (int) $data["vendor_id"];
            $shipping_id = (int) $data["shipping_id"];
            $price = Charset::CleanStr ($data["price"]);
            $days = Charset::CleanStr ($data["days"]);
            $name = '';
        endif;

        // Get all shipping
        $shipping = Model::load ("Admin/ShippingModel")->getShipping ();
        foreach ($shipping as $value)
        {
            if ($value['Id'] == $shipping_id)
            {
                $name = $value['Name'];
                break;
            }
        }

        if ($data == null)
        {
            if (empty ($price) || empty ($days))
            {
                echo json_encode (array (
                    "error" => true,
                    'message' => 'Chekc fields please'
                ));
                return;
            }
        }

        Model::load ("Admin/VendorsModel")->addShippingRecord ($vendor_id, $shipping_id, $price, $days);

        if ($data != NULL)
        {
            return;
        }

        echo json_encode (array (
            "vendor_id" => $vendor_id,
            "shipping_id" => $shipping_id,
            "price" => $price,
            "days" => $days,
            "name" => $name
        ));
    }

    public function SavePriceRanges ()
    {
        if (isset ($_POST["params"])):
            $json_params = json_decode ($_POST["params"], true);
            $i = 0;
            while (true):
                if (isset ($json_params["price-" . $i])):
                    $data = $json_params["price-" . $i];
                    $params = array (
                        'record_id' => $data["record_id"],
                        '_From_Price' => $data["From_Price"],
                        '_To_Price' => $data["To_Price"],
                        '_Add_Multiplier' => $data["Add_Multiplier"],
                        '_Add_Amount' => $data["Add_Amount"],
                        '_Discount_Percent' => $data["Discount_Percent"],
                        '_Discount_Amount' => $data["Discount_Amount"],
                    );
                    Model::load ("Admin/VendorsModel")->savePriceLogic ($params);
                    $i++;
                else:
                    break;
                endif;

            endwhile;
        endif;

        echo json_encode (array (
            'success' => true
        ));
    }

    public function AddPriceRange ($data = null)
    {
        if ($data == null):
            $vendor_id = (int) $_POST["vendor_id"];
            $from = (int) $_POST["from_price"];
            $to = (int) $_POST["to_price"];
            $add_mul = (int) $_POST["add_mult"];
            $add_amount = (int) $_POST["add_amount"];
            $disc_percent = (int) $_POST["disc_percent"];
            $disc_amount = (int) $_POST["disc_amount"];
        else:
            $vendor_id = (int) $data["vendor_id"];
            $from = (int) $data["from_price"];
            $to = (int) $data["to_price"];
            $add_mul = (int) $data["add_mult"];
            $add_amount = (int) $data["add_amount"];
            $disc_percent = (int) $data["disc_percent"];
            $disc_amount = (int) $data["disc_amount"];
        endif;

        if ($to <= $from && $data == null)
        {
            echo json_encode (array (
                "error" => true,
                'message' => 'Chekc price range please'
            ));
            return;
        }

        $result = Model::load ("Admin/VendorsModel")->addPriceRecord ($vendor_id, $from, $to, $add_mul, $add_amount, $disc_percent, $disc_amount);

        if ($data != null)
        {
            return;
        }

        echo json_encode (array (
            "vendor_id" => $vendor_id,
            "price_id" => $result['Id']
        ));
    }

    public function SaveShipping ()
    {
        $vendor_id = (int) $_POST["vendor_id"];
        $shipping_id = (int) $_POST["shipping_id"];
        $field = $_POST["field"];

        if ($field == 'price')
        {
            $price = $_POST["price"];

            Model::load ("Admin/VendorsModel")->saveShippingPrice ($vendor_id, $shipping_id, $price);

            echo json_encode (array (
                "vendor_id" => $vendor_id,
                "shipping_id" => $shipping_id,
                "price" => $price
            ));
        }

        if ($field == 'days')
        {
            $days = $_POST["days"];

            Model::load ("Admin/VendorsModel")->saveShippingDays ($vendor_id, $shipping_id, $days);

            echo json_encode (array (
                "vendor_id" => $vendor_id,
                "shipping_id" => $shipping_id,
                "days" => $days
            ));
        }
    }

    public function SavePersonalInfo ()
    {
        $vendor_id = (int) $_POST["vendor_id"];
        $data = json_decode ($_POST["json"], true);

        if ($data && $vendor_id):

            foreach ($data as $value)
            {
                Model::load ("Admin/VendorsModel")->updateMainInfo ($vendor_id, Charset::CleanStr ($value['name']), Charset::CleanStr ($value['value']));
            }

            echo json_encode (array (
                "vendor_id" => $vendor_id
            ));

            return;
        endif;

        echo json_encode (array (
            "error" => $vendor_id
        ));
    }

    public function ReturnsExchanges ()
    {
        $vendor_id = (int) $_POST["vendor_id"];
        $txt = $_POST["txt"];

        Model::load ("Admin/VendorsModel")->saveReturnsExchanges ($vendor_id, $txt);

        echo json_encode (array (
            "vendor_id" => $vendor_id
        ));
    }
    
    
    public function RemoveVendor()
    {
        $vendor_id = (int) $_POST["id"];
        $status = $_POST["status"];
         Model::load ("Admin/VendorsModel")->ChangeStatus($status, $vendor_id);
         
        echo json_encode (array (
            "vendor_id" => $vendor_id,
            "status" => $status
        ));
       
    }

}