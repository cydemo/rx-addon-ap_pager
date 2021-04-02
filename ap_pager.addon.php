<?php

if ( !defined('__XE__') )
{
	exit();
}

/**
 * @file ap_pager.addon.php
 * @author cydemo (cydemo@gmail.com)
 * @brief ap_pager
 **/

if ( $called_position !== 'before_display_content' || Context::getResponseMethod() !== 'HTML' || Context::get('module') === 'admin' || !Context::get('document_srl') )
{
	return;
}

// Import the Document Module Object by $document_srl
$oDocumentModel = getModel('document');
$oDocument = $oDocumentModel->getDocument(Context::get('document_srl'));

// Basic Variables
$addon_info->page_divider = $addon_info->page_divider ? $addon_info->page_divider : 'page_breaker';
$addon_info->page_count = (int)$addon_info->page_count ? (int)$addon_info->page_count : 5;
$addon_info->skin_path = file_exists('./addons/ap_pager/skins/' . $addon_info->skin . '/index.html') ? $addon_info->skin : 'default';
$addon_info->skin_path = './addons/ap_pager/skins/' . $addon_info->skin_path;

// Page-Seperator Variables
$addon_info->page_starter = $addon_info->page_starter ? $addon_info->page_starter : '[-s]';
$addon_info->page_breaker = $addon_info->page_breaker ? $addon_info->page_breaker : '[-]';
$addon_info->page_finisher = $addon_info->page_finisher ? $addon_info->page_finisher : '[-f]';
$content = $oDocument->get('content');
if ( $addon_info->page_divider === 'page_breaker' )
{
	if ( strpos($content, $addon_info->page_breaker) === false )
	{
		return;
	}
}
else
{
	$image_pattern = "/<p\b[^>]*><img[^>]*src=[\"']?[^>\"']+[\"']?[^>]*><\/p>/i";
	$except_pattetn = "/<p\b[^>]*><img[^>]+style[^>]+display[^>]+none[^>]+><\/p>/i";
	if ( !preg_match($image_pattern, $content) )
	{
		return;
	}
	else
	{
		preg_match_all($image_pattern, $content, $matches);
		$img_tags = array();
		foreach ( $matches[0] as $val )
		{
			if ( !preg_match($except_pattetn, $val) )
			{
				$img_tags[] = $val;
			}
		}
		if ( count($img_tags) > 1 )
		{
			$addon_info->img_tags = array();
			$addon_info->img_tags = $img_tags;
		}
		else
		{
			return;
		}
	}
}

// Variables for the Default Skin
if ( $addon_info->skin == '' || $addon_info->skin == 'default' )
{
	$addon_info->button = is_numeric($addon_info->button) ? $addon_info->button : 1;
	$addon_info->swipe = is_numeric($addon_info->swipe) ? $addon_info->swipe : 1;
	$addon_info->ajax = is_numeric($addon_info->ajax) ? $addon_info->ajax : 1;
	$addon_info->offset = preg_replace('/[\"\']/i', '', $addon_info->offset);
	// Check 'minify_scripts' for Rhymix
	if ( defined('RX_VERSION') && Rhymix\Framework\Config::get('view.minify_scripts') === 'all' )
	{
		$addon_info->minify_scripts = 1;
	}
	else
	{
		$addon_info->minify_scripts = 0;
	}
	Context::addHtmlHeader("<script>
		var pager_swipe = {$addon_info->swipe};
		var pager_ajax = {$addon_info->ajax};
		var pager_ajax_offset = '{$addon_info->offset}';
		var pager_minify_scripts = {$addon_info->minify_scripts};
	</script>");
	Context::loadFile(array($addon_info->skin_path . '/js/default.js', 'body', '', null), true);
	if ( $addon_info->swipe )
	{
		Context::loadFile(array($addon_info->skin_path . '/js/jquery.touchSwipe.min.js', 'body', '', null), true);
	}
}

$GLOBALS['_ap_pager_addon_info_'] = $addon_info;
require_once(_XE_PATH_ . 'addons/ap_pager/ap_pager.func.php');

$output = preg_replace_callback('|<\!--BeforeDocument\(([0-9]+),([0-9]+)\)-->(.*?)<\!--AfterDocument\(([0-9]+),([0-9]+)\)-->|is', 'getContentByUserPage', $output);

$component_pattern = '!<(?:(div)|img)([^>]*)editor_component=([^>]*)>(?(1)(.*?)</div>)!is';
if ( preg_match($component_pattern, $output, $match) )
{
	$output = preg_replace_callback($component_pattern, 'transEditorComponentByUserPage', $output);
}

/* !End of file */

?>
