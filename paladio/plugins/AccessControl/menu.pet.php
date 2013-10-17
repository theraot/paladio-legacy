<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	$validUris = AccessControl::ValidUris();
	$keys = array_keys($validUris);
	$selectedClass = isset($_ELEMENT['attributes']['selected-class']) ? $_ELEMENT['attributes']['selected-class'] : false;
	$itemClass = isset($_ELEMENT['attributes']['item-class']) ? $_ELEMENT['attributes']['item-class'] : false;
	$activeClass = isset($_ELEMENT['attributes']['active-class']) ? $_ELEMENT['attributes']['active-class'] : false;
	$source = $_ELEMENT['source'];
	$path = $_ELEMENT['path']; 
	if ($_ELEMENT['query'] !== '')
	{
		$source .= '?'.$_ELEMENT['query'];
	}
	$tree = array();
	$data = array();
	foreach ($keys as $key)
	{
		$entry = &$validUris[$key];
		$entry['_link'] = $path.'/'.$key;
		if (isset($entry['menu-title']))
		{
			if ($entry['path'] == $source)
			{
				$entry['selected'] = true;
			}
			if (isset($entry['menu-parent']))
			{
				$parentId = $entry['menu-parent'];
				if (!array_key_exists($parentId, $data))
				{
					$data[$parentId] = array('_childs' => array());
				}
				else if (!array_key_exists('_childs', $data[$parentId]))
				{
					$data[$parentId]['_childs'] = array();
				}
				if (isset($entry['menu-id']))
				{
					$data[$parentId]['_childs'][] = &$entry;
					$data[$entry['menu-id']] = &$entry;
				}
				else
				{
					$data[$parentId]['_childs'][] = &$entry;
				}
				if (array_key_exists('selected', $entry) || array_key_exists('active', $entry))
				{
					$data[$parentId]['active'] = true;
				}
			}
			else
			{
				$tree[] = &$entry;
				if (isset($entry['menu-id']))
				{
					$data[$entry['menu-id']] = &$entry;
				}
			}
		}
	}
	
	if (!function_exists("EmitPaladioNavMenu"))
	{
		function __EmitPaladioNavMenu($class, $itemClass, $selectedClass, $activeClass, $entries)
		{
			if (is_null($class))
			{
				echo '<ul>';
			}
			else
			{
				echo '<ul class="'.$class.'">';
			}
			foreach ($entries as $entry)
			{
				echo '<li';
				$classes = array();
				if ($itemClass !== false)
				{
					$classes[] = $itemClass;
				}
				if ($selectedClass !== false && array_key_exists('selected', $entry))
				{
					$classes[] = $selectedClass;
				}
				if ($activeClass !== false && array_key_exists('active', $entry))
				{
					$classes[] = $activeClass;
				}
				if (count($classes) > 0)
				{
					echo ' class="';
					echo implode(' ', $classes);
					echo '"';
				}
				if (isset($entry['menu-id']))
				{
					echo ' id="'.$entry['menu-id'].'"';
				}
				echo '><a ';
				if (isset($entry['_link']))
				{
					echo 'href="'.$entry['_link'].'"';
				}
				if (isset($entry['menu-target']))
				{
					echo 'target="'.$entry['menu-target'].'"';
				}
				echo '>';
				if (isset($entry['menu-title']))
				{
					echo $entry['menu-title'];
				}
				echo '</a>';
				if (isset($entry['_childs']))
				{
					__EmitPaladioNavMenu(null, $itemClass, $selectedClass, $activeClass, $entry['_childs']);
				}
				echo '</li>';
			}
			echo '</ul>';
		}
	}
	
	echo '<nav>';
	if (isset($_ELEMENT['attributes']['class']))
	{
		__EmitPaladioNavMenu($_ELEMENT['attributes']['class'], $itemClass, $selectedClass, $activeClass, $tree);
	}
	else
	{
		__EmitPaladioNavMenu(null, $itemClass, $selectedClass, $activeClass, $tree);
	}
	echo '</nav>';
?>