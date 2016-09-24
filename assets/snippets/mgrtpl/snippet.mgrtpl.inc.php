<?php
$get = isset($get) ? $get : '';
$outerTpl = isset($outerTpl) ? $outerTpl : '';
$rowTpl = isset($rowTpl) ? $rowTpl : '';
$useTabs = isset($useTabs) ? $useTabs : '';
$useSections = isset($useSections) ? $useSections : '';
$filter = isset($filter) ? $filter : '';
$sortBy = isset($sortBy) ? $sortBy : '';
$category = isset($category) ? $category : '';
$element = isset($element) ? $element : '';

$tpe =& $modx->manager->tpl;

switch($get) {
	case 'css':
		return $tpe->mergeDomCss();
		break;
	case 'javascript':
		return $tpe->mergeDomJs();
		break;
	case 'buttons':
		return $tpe->mergeDomActionButtons($category, $outerTpl, $rowTpl);
		break;
	case 'alerts':
		return $tpe->mergeDomAlerts($outerTpl, $rowTpl);
		break;
	case 'body':
		return $tpe->mergeDomBody();
		break;
	case 'element':
		return $tpe->mergeElement($element);
		break;
	case 'inputs':
		return $tpe->mergeFormInputs($form, $outerTpl, $rowTpl, $useTabs, $useSections, $filter, $sortBy);
		break;
	case 'footer':
		// return $tpe->mergeDomActionButtons($outerTpl, $rowTpl);
		break;
}
return '';
?>