<?php

if ( !defined('__XE__') )
{
	exit();
}

/**
 * @file ap_pager.func.php
 * @author cydemo (cydemo@gmail.com)
 * @brief Function collections for the implementation of ap_pager
 **/

function getContentByUserPage()
{
	// Import document information
	$oDocumentModel = getModel('document');
	$oDocument = $oDocumentModel->getDocument(Context::get('document_srl'));

	// Import content division information
	$addon_info = $GLOBALS['_ap_pager_addon_info_'];
	$page_divider = $addon_info->page_divider;
	$page_starter = $addon_info->page_starter;
	$page_breaker = $addon_info->page_breaker;
	$page_finisher = $addon_info->page_finisher;
	$page_upper = $addon_info->page_upper;
	$page_under = $addon_info->page_under;

	// Divide content by the separators
	$_cnt = $oDocument->get('content');
	$pre_cnt = '';
	$post_cnt = '';
	$_cnt_arr = array();
	if ( $page_divider === 'page_breaker' )
	{
		// divide the content by the starter
		if ( strpos($_cnt, $page_starter) !== false )
		{
			$_cnt = preg_replace('/<p\b[^>]*>(\\'.$page_starter.')<\/p>/i', '$1', $_cnt);
			$_cnt_arr = explode($page_starter, $_cnt);
			$pre_cnt = $_cnt_arr[0];
			$_cnt = $_cnt_arr[1];
		}

		// divide the content by the finisher
		if ( strpos($_cnt, $page_finisher) !== false )
		{
			$_cnt = preg_replace('/<p\b[^>]*>(\\'.$page_finisher.')<\/p>/i', '$1', $_cnt);
			$_cnt_arr = explode($page_finisher, $_cnt);
			$post_cnt = $_cnt_arr[1];
			$_cnt = $_cnt_arr[0];
		}

		// send the trimmed array to the content variable
		$_cnt = preg_replace('/<p\b[^>]*>(\\'.$page_breaker.')<\/p>/i', '$1', $_cnt);
		$cnt = explode($page_breaker, $_cnt);
	}
	else
	{
		// remove the marks of starter and finisher
		$_cnt = preg_replace('/<p\b[^>]*>\\'.$page_starter.'<\/p>/i', '', $_cnt);
		$_cnt = preg_replace('/<p\b[^>]*>\\'.$page_finisher.'<\/p>/i', '', $_cnt);

		// divide the content by img tags
		$img_tags = $addon_info->img_tags;
		$_cnt_arr = explode($img_tags[0], $_cnt);
		$pre_cnt = $_cnt_arr[0];
		$_cnt_arr = explode(end($img_tags), $_cnt);
		$post_cnt = $_cnt_arr[1];

		// send the image array to the content variable
		$cnt = $img_tags;
	}

	// Set User Page Navigation Definition
	$total_page = count($cnt);
	if ( !Context::get('upage') )
	{
		Context::set('upage', 1);
	}
	$upage = abs(Context::get('upage'));
	if ( $upage > $total_page )
	{
		$upage = $total_page;
	}

	// total_count, total_page, cur_page, page_count
	if ( $total_page > 1 )
	{
		$_page_navigation = new PageHandler($total_page, $total_page, $upage, $addon_info->page_count);
	}

	// Set variables for skins
	Context::set('ap_pager', $addon_info);
	Context::set('ap_page_navigation', $_page_navigation);

	// Compile template
	$oTemplate = &TemplateHandler::getInstance();
	$addon_output = $oTemplate->compile($addon_info->skin_path, 'index.html');
	if ( $addon_info->button )
	{
		$addon_button = $oTemplate->compile($addon_info->skin_path, 'button.html');
	}

	// Mainpulate the content variable
	$content = $cnt[$upage - 1];
	$member_srl = $oDocument->get('member_srl');
	if ( $member_srl < 0 )
	{
		$member_srl = 0;
	}
	$content = sprintf(
		'<!--BeforeDocument(%d,%d)--><div class="document_%d_%d xe_content">%s</div><!--AfterDocument(%d,%d)-->',
		$oDocument->document_srl, $member_srl,
		$oDocument->document_srl, $member_srl,
		$pre_cnt . $page_upper . '<div class="ap_pager_content">' . $content . $addon_button . '</div>' . $page_under . $addon_output . $post_cnt,
		$oDocument->document_srl, $member_srl,
		$oDocument->document_srl, $member_srl
	);

	return $content;
}

function transEditorComponentByUserPage($match)
{
	$script = " {$match[2]} editor_component={$match[3]}";
	$script = preg_replace('/([\w:-]+)\s*=(?:\s*(["\']))?((?(2).*?|[^ ]+))\2/i', '\1="\3"', $script);
	preg_match_all('/([a-z0-9_-]+)="([^"]+)"/is', $script, $m);

	$xml_obj = new stdClass;
	$xml_obj->attrs = new stdClass;
	for ( $i = 0, $c = count($m[0]); $i <$c; $i++ )
	{
		if ( !isset($xml_obj->attrs) )
		{
			$xml_obj->attrs = new stdClass;
		}
		$xml_obj->attrs->{$m[1][$i]} = $m[2][$i];
	}
	$xml_obj->body = $match[4];

	if ( !$xml_obj->attrs->editor_component )
	{
		return $match[0];
	}

	// Get converted codes by using component::transHTML()
	$oEditorModel = getModel('editor');
	$oComponent = &$oEditorModel->getComponentObject($xml_obj->attrs->editor_component, 0);
	if ( !is_object($oComponent) || !method_exists($oComponent, 'transHTML') )
	{
		return $match[0];
	}

	return $oComponent->transHTML($xml_obj);
}

/* End of file ap_pager.func.php */
/* Location: ./addons/ap_pager/ap_pager.func.php */