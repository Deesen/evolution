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
	// Render alerts array
	case 'alerts':
		return $tpe->mergeDomAlerts($outerTpl, $rowTpl);
		break;
	case 'element':
		return $tpe->renderBodyElementsRecursive($element, false, true);
		break;
	case 'elements':
		return $tpe->renderBodyElementsRecursive($element, true, true);
		break;
	case 'list':
		return $tpe->mergeElementsList($element, $depth, $outerTpl, $rowTpl, $cssFirst, $cssLast);
		break;
	case 'footer':
		// @todo: Proposal: New optional Footer-bar for infos, buttons etc..
		return '';

}

return $get ? 'Snippet mgrTpl: Get-Command '.$get.' not found' : '';
?>