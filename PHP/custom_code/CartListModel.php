<?php

class CartListModel
{
    private $PDO = null;

    public function __construct ()
    {
        // Getting connection to the database
        $this->PDO = Database::getConnection ();
    }

    /**
     * Add product to cart list
     * @param $sess_record_id
     * @param $user_id
     * @param $prod_id
     * @param $db_id
     * @param $options
     * @return null
     */
    public function add ($sess_record_id, $user_id, $prod_id, $db_id = 1, $options)
    {
        $record_id = null;

        try
        {
            $statement = $this->PDO->prepare ("CALL shop_cart_add(?, ?, ?, ?, ?);");
            $statement->bindParam (1, $sess_record_id, PDO::PARAM_INT);
            $statement->bindParam (2, $user_id, PDO::PARAM_INT);
            $statement->bindParam (3, $prod_id, PDO::PARAM_INT);
            $statement->bindParam (4, $db_id, PDO::PARAM_INT);
            $statement->bindParam (5, $options, PDO::PARAM_STR);
            $statement->execute ();
            $record_id = $statement->fetch (PDO::FETCH_ASSOC);
            $statement->nextRowset ();
        }
        catch (Exception $e)
        {
            Logs::add ($e);
        }

        return $record_id;
    }

    /**
     * Get product from cart list
     * @param $sess_id - record id
     * @param $user_id
     * @return null
     */
    public function get ($sess_id, $user_id)
    {
        $result = null;

        try
        {
            // Temp arrays
            $data_arr = null;
            $item_arr = null;
            $statement = $this->PDO->prepare ("CALL shop_cart_get(?, ?);");
            $statement->bindParam (1, $sess_id, PDO::PARAM_INT);
            $statement->bindParam (2, $user_id, PDO::PARAM_INT);
            $statement->execute ();
            $data_arr = $statement->fetchAll (PDO::FETCH_ASSOC);
            $statement->nextRowset ();
            $item_arr = $statement->fetchAll (PDO::FETCH_ASSOC);
            $statement->nextRowset ();

            // Marge arrays
            if ($data_arr && $item_arr)
            {
                // Dates
                foreach ($data_arr as $value)
                {
                    // Items - search item by id
                    foreach ($item_arr as $item)
                    {
                        if ($item["Product_Id"] == $value["Product_Id"])
                        {
                            // Add params
                            $item["Count"] = $value["Count"];
                            $item["Shipping_Id"] = $value["Shipping_Id"];
                            $item["Option_Ids"] = $value["Option_Ids"];
                            $item["Datetime"] = $value["Datetime"];

                            // Add to result
                            $result[] = $item;
                        }
                    }
                }
            }
        }
        catch (Exception $e)
        {
            Logs::add ($e);
        }

        return $result;
    }

    /**
     * Get count products from cart list
     * @param $sess_id
     * @param $user_id
     * @return null
     */
    public function getCount ($sess_id, $user_id)
    {
        $result = null;

        try
        {
            $statement = $this->PDO->prepare ("CALL shop_cart_getCount(?, ?);");
            $statement->bindParam (1, $sess_id, PDO::PARAM_INT);
            $statement->bindParam (2, $user_id, PDO::PARAM_INT);
            $statement->execute ();
            $result = $statement->fetch (PDO::FETCH_ASSOC);
            $statement->nextRowset ();
        }
        catch (Exception $e)
        {
            Logs::add ($e);
        }

        return $result;
    }

    /**
     * Link cart list from session id to user id
     * @param $sess_id
     * @param $user_id
     */
    public function linkToUser ($sess_id, $user_id)
    {
        try
        {
            $statement = $this->PDO->prepare ("CALL shop_cart_linkToUser(?, ?);");
            $statement->bindParam (1, $sess_id, PDO::PARAM_INT);
            $statement->bindParam (2, $user_id, PDO::PARAM_INT);
            $statement->execute ();
            $statement->nextRowset ();
        }
        catch (Exception $e)
        {
            Logs::add ($e);
        }
    }

    /**
     * Remove item from cart list
     * @param $prod_id
     * @param $db_id
     * @param $sess_id
     * @param $user_id
     */
    public function remove ($prod_id, $db_id, $sess_id, $user_id)
    {
        try
        {
//            echo "CALL shop_cart_remove($prod_id, $db_id, $sess_id, $user_id);";
            $statement = $this->PDO->prepare ("CALL shop_cart_remove(?, ?, ?, ?);");
            $statement->bindParam (1, $prod_id, PDO::PARAM_INT);
            $statement->bindParam (2, $db_id, PDO::PARAM_INT);
            $statement->bindParam (3, $sess_id, PDO::PARAM_INT);
            $statement->bindParam (4, $user_id, PDO::PARAM_INT);
            $statement->execute ();
            $statement->nextRowset ();
        }
        catch (Exception $e)
        {
            Logs::add ($e);
        }
    }

    /**
     * Set new count
     * @param $prod_id
     * @param $db_id
     * @param $sess_id
     * @param $user_id
     * @param $newCount
     */
    public function updateCount ($prod_id, $db_id, $sess_id, $user_id, $newCount)
    {
        try
        {
            $statement = $this->PDO->prepare ("CALL shop_cart_updateCount(?, ?, ?, ?, ?);");
            $statement->bindParam (1, $prod_id, PDO::PARAM_INT);
            $statement->bindParam (2, $db_id, PDO::PARAM_INT);
            $statement->bindParam (3, $sess_id, PDO::PARAM_INT);
            $statement->bindParam (4, $user_id, PDO::PARAM_INT);
            $statement->bindParam (5, $newCount, PDO::PARAM_INT);
            $statement->execute ();
            $statement->nextRowset ();
        }
        catch (Exception $e)
        {
            Logs::add ($e);
        }
    }

    /**
     * Update shipping for Cart:
     * @param $prod_id
     * @param $db_id
     * @param $sess_id
     * @param $user_id
     * @param $shippingId
     */
    public function updateShipping ($prod_id, $db_id, $sess_id, $user_id, $shippingId)
    {
        try
        {
            $statement = $this->PDO->prepare ("CALL shop_cart_updateShipping(?, ?, ?, ?, ?);");
            $statement->bindParam (1, $prod_id, PDO::PARAM_INT);
            $statement->bindParam (2, $db_id, PDO::PARAM_INT);
            $statement->bindParam (3, $sess_id, PDO::PARAM_INT);
            $statement->bindParam (4, $user_id, PDO::PARAM_INT);
            $statement->bindParam (5, $shippingId, PDO::PARAM_INT);
            $statement->execute ();
            $statement->nextRowset ();
        }
        catch (Exception $e)
        {
            Logs::add ($e);
        }
    }

    /**
     * Remove all items from cart for user or session:
     * @param $sess_id
     * @param $user_id
     */
    public function cart_clear ($sess_id, $user_id)
    {
        try
        {
            $statement = $this->PDO->prepare ("CALL shop_cart_clear(?, ?);");
            $statement->bindParam (1, $sess_id, PDO::PARAM_INT);
            $statement->bindParam (2, $user_id, PDO::PARAM_INT);
            $statement->execute ();
            $statement->nextRowset ();
        }
        catch (Exception $e)
        {
            Logs::add ($e);
        }
    }

}