<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	$validUris = AccessControl::ValidUris();
	$keys = array_keys($validUris);
	$selectedClass = array_key_exists('selected-class', $_ELEMENT['attributes']) ? $_ELEMENT['attributes']['selected-class'] : false;
	$itemClass = array_key_exists('item-class', $_ELEMENT['attributes']) ? $_ELEMENT['attributes']['item-class'] : false;
	$activeClass = array_key_exists('active-class', $_ELEMENT['attributes']) ? $_ELEMENT['attributes']['active-class'] : false;
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
		if (array_key_exists('menu-title', $entry))
		{
			if ($entry['path'] == $source)
			{
				$entry['selected'] = true;
			}
			if (array_key_exists('menu-parent', $entry))
			{
				$virtual = 0;
				$parentId = $entry['menu-parent'];
				$array = array();
				if (is_array($parentId))
				{
					$virtual = 1;
					$array = array_merge($array, $parentId);
					if (array_key_exists('menu-id', $parentId))
					{
						$parentId = $parentId['menu-id'];
						$entry['menu-parent'] = $parentId;
					}
				}
				if (!array_key_exists($parentId, $data))
				{
					$data[$parentId] = $array;
					if ($virtual == 1)
					{
						$virtual = 2;
					}
					$data[$parentId]['_childs'][] = &$entry;
				}
				else
				{
					if (array_key_exists('_childs', $data[$parentId]))
					{
						$data[$parentId]['_childs'][] = &$entry;
					}
					else
					{
						$data[$parentId]['_childs'] = array(&$entry);
					}
					$data[$parentId] = array_merge($array, $data[$parentId]);
				}
				if (array_key_exists('menu-id', $entry))
				{
					$data[$entry['menu-id']] = &$entry;
				}
				if (array_key_exists('selected', $entry) || array_key_exists('active', $entry))
				{
					$data[$parentId]['active'] = true;
				}
				if ($virtual == 2)
				{
					$tree[] = &$data[$parentId];
				}
			}
			else
			{
				$tree[] = &$entry;
				if (array_key_exists('menu-id', $entry))
				{
					$data[$entry['menu-id']] = &$entry;
				}
			}
		}
	}
	if (!function_exists("EmitPaladioNavMenu"))
	{
		function __EmitPaladioNavMenu($id, $class, $itemClass, $selectedClass, $activeClass, $entries)
		{
			if (is_null($id))
			{
				if (is_null($class))
				{
					echo '<ul>';
				}
				else
				{
					echo '<ul class="'.$class.'">';
				}
			}
			else
			{
				if (is_null($class))
				{
					echo '<ul id="'.$id.'">';
				}
				else
				{
					echo '<ul id="'.$id.'" class="'.$class.'">';
				}
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
				if (array_key_exists('menu-id', $entry))
				{
					echo ' id="'.$entry['menu-id'].'"';
				}
				echo '><a ';
				if (array_key_exists('_link', $entry))
				{
					echo 'href="'.$entry['_link'].'"';
				}
				if (array_key_exists('menu-target', $entry))
				{
					echo 'target="'.$entry['menu-target'].'"';
				}
				echo '>';
				if (array_key_exists('menu-title', $entry))
				{
					echo $entry['menu-title'];
				}
				echo '</a>';
				if (array_key_exists('_childs', $entry))
				{
					__EmitPaladioNavMenu(null, null, $itemClass, $selectedClass, $activeClass, $entry['_childs']);
				}
				echo '</li>';
			}
			echo '</ul>';
		}
	}
	
	echo '<nav>';
	__EmitPaladioNavMenu
		(
			array_key_exists('id', $_ELEMENT['attributes']) ? $_ELEMENT['attributes']['id'] : null,
			array_key_exists('class', $_ELEMENT['attributes']) ? $_ELEMENT['attributes']['class'] : null,
			$itemClass,
			$selectedClass,
			$activeClass,
			$tree
		);
	echo '</nav>';
?>