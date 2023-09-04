<?php

use Bitrix\Sale\Order,
    Bitrix\Main\Type\Date;

function UpdateCheckOrder()
{
    define("CHECKED_ORDER_PROPERTY_ID", 20);

    Bitrix\Main\Loader::includeModule("sale");
    Bitrix\Main\Loader::includeModule("catalog");

    $dateTo = new Date();
    $dateFrom = new Date();
    $dateTo->add("-3 day");
    $dateFrom->add("-7 day");

    $filter = array(
        ">=DATE_PAYED" => $dateFrom,
        "<=DATE_PAYED" => $dateTo,
        "PROPERTY.ORDER_PROPS_ID" => CHECKED_ORDER_PROPERTY_ID,
        "PROPERTY.VALUE" => 'N',
        "CANCELED" => "N"
    );

    $orderList = Order::getList(array(
        "filter" => $filter,
        "select" => array("ID", "DATE_PAYED"),
    ));

    while ($order = $orderList->fetch()) {
        $orderId = $order["ID"];

        $objOrder = Order::load($orderId);
        if ($objOrder) {
            $propertyCollection = $objOrder->getPropertyCollection();

            $checkedProp = $propertyCollection->getItemByOrderPropertyId(CHECKED_ORDER_PROPERTY_ID);
            $checkedProp->setValue('Y');
            $objOrder->doFinalAction(true);
            $result = $objOrder->save();
            if ($result) {
                $rsUser = CUser::GetByID($objOrder->getUserId());
                $arUser = $rsUser->Fetch();
                $message = json_encode([
                    "ID" => $objOrder->getId(),
                    "DATE_PAYED" => $objOrder->getField("DATE_PAYED")->format("Y-m-d"),
                    "EMAIL" => $propertyCollection->getUserEmail()->getValue(),
                    "NAME" => $propertyCollection->getPayerName()->getValue(),
                    "DATE_LAST_UPDATE_CHECKED_PROP" => date("Y-m-d")
                ]);

                CEventLog::Add(array(
                    "SEVERITY" => "SECURITY",
                    "AUDIT_TYPE_ID" => "UPDATE_ORDER_CHECk",
                    "MODULE_ID" => "main",
                    "ITEM_ID" => 123,
                    "DESCRIPTION" => $message,
                ));
            }
        }
    }
    return "UpdateCheckOrder();";
}
