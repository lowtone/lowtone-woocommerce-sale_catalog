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

	// Add rewrite rules

	add_action("init", function() {
		if (NULL === ($page = saleCatalogPage()))
			return;

		addRewriteRules($page);

		add_rewrite_tag('%sale%','([^&]+)');
	});

	// Modify query

	add_filter("pre_get_posts", function($query) {
		if (!$query->is_main_query())
			return $query;

		if (!$query->get("sale"))
			return $query;

		$query->set("meta_query", array(
				array(
					"key" => "_visibility",
					"value" => array("catalog", "visible"),
					"compare" => "IN",
				),
				array(
					"key" => "_sale_price",
					"value" => 0,
					"compare" => ">",
					"type" => "NUMERIC",
				)
			));
		
		return $query;
	}, 0);

	// Set title on sale page

	add_filter("woocommerce_page_title", function($title) {
		if (!isSaleCatalog())
			return $title;

		if (NULL === ($page = saleCatalogPage()))
			return $title;

		return $page->post_title;
	});

	// Add sale page selection

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

	// Update & flush rewrite rules

	add_action("update_option_lowtone_woocommerce_sale_catalog_page_id", function($old, $new) {
		if (NULL === ($page = get_post($new)))
			return;

		addRewriteRules($page);

		flush_rewrite_rules();
	}, 10, 2);

	// Register textdomain
	
	add_action("plugins_loaded", function() {
		load_plugin_textdomain("lowtone_woocommerce_sale_catalog", false, basename(__DIR__) . "/assets/languages");
	});

	// Functions
	
	function isSaleCatalog() {
		wp_reset_query();

		return is_archive() && get_query_var("sale");
	}

	function saleCatalogPageId() {
		return get_option("lowtone_woocommerce_sale_catalog_page_id") ?: NULL;
	}

	function saleCatalogPage() {
		if (NULL === ($pageId = saleCatalogPageId()))
			return;

		return get_post($pageId) ?: NULL;
	}

	function addRewriteRules($page) {
		global $wp_rewrite;

		$saleSlug = $page->post_name;

		add_rewrite_rule("{$saleSlug}/?$", ($urlBase = "index.php?post_type=product&sale=1"), "top");
		
		$feeds = "(" . trim(implode("|", $wp_rewrite->feeds)) . ")";
		
		add_rewrite_rule("{$saleSlug}/feed/{$feeds}/?$", "{$urlBase}&feed=\$matches[1]", "top");
		add_rewrite_rule("{$saleSlug}/{$feeds}/?$", "{$urlBase}&feed=\$matches[1]", "top");
			
		add_rewrite_rule("{$saleSlug}/{$wp_rewrite->pagination_base}/([0-9]{1,})/?$", "{$urlBase}&paged=\$matches[1]", "top");
	}

}