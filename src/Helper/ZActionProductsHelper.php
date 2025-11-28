<?php
namespace Zabba\Module\ZActionProducts\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

class ZActionProductsHelper
{
    /**
     * Return 4 random product IDs from a category
     */
    public function getProductsIds($params)
    {
	$category = (int) $params->get('virtuemart_category_id', 0);
	$actionProducts = (int) $params->get('action_products', 0);
        $db       = Factory::getContainer()->get(DatabaseInterface::class);
	If ($actionProducts === 0){
	    $query = $db->getQuery(true)
		->select($db->quoteName('virtuemart_product_id'))
		->from($db->quoteName('#__virtuemart_product_categories'))
		->where($db->quoteName('virtuemart_category_id') . ' = :category')
		->order('RAND()')
		->bind(':category', $category);
	}
	else{
	    $query = $db->getQuery(true)
		->select($db->quoteName('virtuemart_product_id'))
		->from($db->quoteName('#__virtuemart_products'))
		->where($db->quoteName('product_special') . ' = :actionProducts')
		->order('RAND()')
		->bind(':actionProducts', $actionProducts);
	}
        $db->setQuery($query, 0, 4);
        return $db->loadColumn();
    }

    /**
     * Return full product data for the selected IDs
     */
    public function getProducts($params)
    {
        $app    = Factory::getApplication();
        $user   = $app->getIdentity();
        $userId = (int) $user->id;

        $ids = $this->getProductsIds($params);
        if (!$ids) {
            return [];
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
	$obchod = $params->get('obchod', '2', 'STRING'); //velkoobchod = 1, maloobchod = 2
	if ($obchod ==='1') {

	    // Determine user's shopper group
	    $qGroup = $db->getQuery(true)
		->select($db->quoteName('virtuemart_shoppergroup_id'))
		->from($db->quoteName('#__virtuemart_vmuser_shoppergroups'))
		->where($db->quoteName('virtuemart_user_id') . '= :userid')
	        ->bind(':userid', $userId);

	    $db->setQuery($qGroup);
	    $groupRow = $db->loadRow();
	    $shopperGroup = $groupRow[0] ?? 5;
	    }
	else {
	    $shopperGroup = 0;
	    }
	$published = 1;
        // Main query
        $q = $db->getQuery(true)
            ->select([
                't1.virtuemart_product_id',
                't3.product_in_stock',
                't1.product_name',
                't2.virtuemart_category_id',
                't3.product_availability',
                't4.product_price',
                't6.file_url',
                't3.product_params',
                't7.calc_value',
                't9.mf_name',
                't9.virtuemart_manufacturer_id'
            ])
            ->from($db->quoteName('#__virtuemart_products_cs_cz', 't1'))
            ->join('INNER', '#__virtuemart_product_prices AS t4 ON t1.virtuemart_product_id = t4.virtuemart_product_id')
            ->join('INNER', '#__virtuemart_products AS t3 ON t1.virtuemart_product_id = t3.virtuemart_product_id')
            ->join('INNER', '#__virtuemart_product_medias AS t5 ON t1.virtuemart_product_id = t5.virtuemart_product_id')
            ->join('INNER', '#__virtuemart_medias AS t6 ON t5.virtuemart_media_id = t6.virtuemart_media_id')
            ->join('INNER', '#__virtuemart_calcs AS t7 ON t4.product_tax_id = t7.virtuemart_calc_id')
            ->join('INNER', '#__virtuemart_product_manufacturers AS t8 ON t1.virtuemart_product_id = t8.virtuemart_product_id')
            ->join('INNER', '#__virtuemart_manufacturers_cs_cz AS t9 ON t8.virtuemart_manufacturer_id = t9.virtuemart_manufacturer_id')
            ->join('LEFT', '#__virtuemart_product_categories AS t2 ON t1.virtuemart_product_id = t2.virtuemart_product_id')
            ->where('t1.virtuemart_product_id IN (' . implode(',', $ids) . ')')
            ->where('t4.virtuemart_shoppergroup_id = :shopperGroup')
	    ->where('t3.published = :published')
	    ->bind(':published', $published)
	    ->bind(':shopperGroup', $shopperGroup);
    
        $db->setQuery($q);
        $rows = $db->loadAssocList();

        if (!$rows) {
            return [];
        }

        $products = [];
        foreach ($rows as $row) {
            $paramsPack = $this->getBaleni($row['product_params']);
            $price      = round($row['product_price'], 2);
            $priceVat   = round($price * (1 + $row['calc_value'] / 100), 2);

            $products[$row['virtuemart_product_id']] = [
                'virtuemart_product_id'  => $row['virtuemart_product_id'],
                'product_name'           => $row['product_name'],
                'virtuemart_category_id' => $row['virtuemart_category_id'],
                'product_availability'   => $row['product_availability'],
                'product_price'          => $price,
                's_dph'                  => $priceVat,
                'file_url'               => $row['file_url'],
                'manufacturer'           => $row['mf_name'],
                'manufacturer_id'        => $row['virtuemart_manufacturer_id'],
                'min_order_level'        => $paramsPack['min_order_level'],
                'step_order_level'       => $paramsPack['step_order_level'],
                'product_in_stock'       => $row['product_in_stock']
            ];
        }

        // Preserve original ID order
        $final = [];
        foreach ($ids as $id) {
            if (isset($products[$id])) {
                $final[] = $products[$id];
            }
        }

        return $final;
    }

    /**
     * Parse VM product_params
     */
public static function getBaleni($input)
{
    $defaults = [
        'min_order_level'  => 1,
        'step_order_level' => 1
    ];

    if (!$input) {
        return $defaults;
    }

    $out = [];

    // min_order_level
    if (preg_match('/min_order_level="([^"]*)"/', $input, $m)) {
        $out['min_order_level'] = ($m[1] !== '') ? (int)$m[1] : 1;
    }

    // step_order_level
    if (preg_match('/step_order_level="([^"]*)"/', $input, $m)) {
        $out['step_order_level'] = ($m[1] !== '') ? (int)$m[1] : 1;
    }

    return array_merge($defaults, $out);
}

}
