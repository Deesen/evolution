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
$depth = isset($depth) ? (int)$depth : 0;
$cssFirst = isset($cssFirst) ? $cssFirst : '';
$cssLast = isset($cssLast) ? $cssLast : '';

$set = isset($set) ? $set : '';
$value = isset($value) ? $value : '';

$tpe =& $modx->manager->tpe;

if($set) {
	if(isset($tpe->tpeOptions[$set])) {
		$tpe->tpeOptions[$set] = $value;
	}
	return '';
}

switch($get) {
	case 'buttons':
		return $tpe->mergeDomActionButtons($category);
		break;
	case 'alerts':
		return $tpe->mergeDomAlerts($outerTpl, $rowTpl);
		break;
	case 'action':
		return $tpe->actionHtml;
		break;
	case 'element':
		return $tpe->mergeElement($element);
		break;
	case 'list':
		return $tpe->mergeElementsList($element, $depth, $outerTpl, $rowTpl, $cssFirst, $cssLast);
		break;

}

return '';
?>