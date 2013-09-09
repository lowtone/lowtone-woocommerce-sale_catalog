<?php
/*
 * Plugin Name: Sale Catalog for WooCommerce
 * Plugin URI: http://wordpress.lowtone.nl/plugins/woocommerce-sale_catalog/
 * Description: Use a page to display all products on sale.
 * Version: 1.0
 * Author: Lowtone <info@lowtone.nl>
 * Author URI: http://lowtone.nl
 * License: http://wordpress.lowtone.nl/license
 */
/**
 * @author Paul van der Meijs <code@lowtone.nl>
 * @copyright Copyright (c) 2011-2013, Paul van der Meijs
 * @license http://wordpress.lowtone.nl/license/
 * @version 1.0
 * @package wordpress\plugins\lowtone\woocommerce\sale_catalog
 */

namespace lowtone\woocommerce\sale_catalog {

	add_filter("pre_get_posts", function($query) {
		if (!isSaleCatalog())
			return $query;

		if (!$query->is_main_query())
			return $query;

		global $woocommerce;

		$query->set("pagename", "");

		$woocommerce->query->product_query($query);

		$productsOnSale = woocommerce_get_product_ids_on_sale();

		if ($postIn = $query->get("post__in"))
			$productsOnSale = array_intersect_key($productsOnSale, $postIn);

		$query->set("post__in", $productsOnSale);
		
		return $query;
	}, 0);

	add_filter("woocommerce_page_settings", function($settings) {
		$offset = 1;

		foreach ($settings as $input) {
			if (isset($input["id"]) && "woocommerce_shop_page_id" == $input["id"])
				break;

			$offset++;
		}

		$input = array(
				"title" => __("Sale page", "lowtone_woocommerce_sale_catalog"),
				"desc" => __("This page displays a catalog of products on sale.", "lowtone_woocommerce_sale_catalog"),
				"id" => "lowtone_woocommerce_sale_catalog_page_id",
				"type" => "single_select_page",
				"default" => "",
				"class"	=> "chosen_select_nostd",
				"css" => "min-width:300px;",
				"desc_tip"=>  true,
			);

		array_splice($settings, $offset, 0, array($input));

		return $settings;
	});

	// Functions
	
	function isSaleCatalog() {
		return apply_filters("is_sale_catalog", is_page(getSaleCatalogPageId()));
	}

	function getSaleCatalogPageId() {
		return get_option("lowtone_woocommerce_sale_catalog_page_id") ?: NULL;
	}

	/*add_filter("pre_get_posts", function($query) {
		var_dump($query);exit;
		
		return $query;
	}, 9999);*/

}