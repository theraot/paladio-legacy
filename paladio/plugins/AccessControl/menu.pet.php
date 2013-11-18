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
	$entries = array();
	$data = array();
	foreach ($keys as $key)
	{
		$entry = &$validUris[$key];
		$entry['_link'] = $path.'/'.$key;
		if (array_key_exists('menu-title', $entry))
		{
			if (is_string($entry['path']))
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
						if (array_key_exists('menu-key', $data[$parentId]))
						{
							$entries[$data[$parentId]['menu-key']] = &$data[$parentId];
						}
						else
						{
							$entries[] = &$data[$parentId];
						}
					}
				}
				else
				{
					if (array_key_exists('menu-key', $entry))
					{
						$entries[$entry['menu-key']] = &$entry;
					}
					else
					{
						$entries[] = &$entry;
					}
					if (array_key_exists('menu-id', $entry))
					{
						$data[$entry['menu-id']] = &$entry;
					}
				}
			}
		}
	}
	if (!function_exists("__EmitPaladioNavMenu"))
	{
		function __EmitPaladioNavMenu($attributes, $itemClass, $selectedClass, $activeClass, $entries, &$index)
		{
			if (!function_exists("cmp"))
			{
				function cmp($a, $b)
				{
					if ($a === $b)
					{
						return 0;
					}
					else
					{
						if (is_string($a))
						{
							if (is_string($b))
							{
								return ($a < $b) ? -1 : 1;
							}
							else
							{
								return 1;
							}
						}
						else
						{
							if (is_string($b))
							{
								return -1;
							}
							else
							{
								return ($a < $b) ? -1 : 1;
							}
						}
					}
				}
			}
			echo '<ul'.PET_Utility::BuildAttributesString($attributes).'>';
			$keys = array_keys($entries);
			usort($keys, "cmp");
			foreach ($keys as $key)
			{
				$entry = $entries[$key];
				echo '<li tabindex="'.$index.'"';
				echo PET_Utility::BuildClassesString
				(
					array
					(
						$itemClass !== false ? $itemClass : null, 
						$selectedClass !== false && array_key_exists('selected', $entry) ? $selectedClass : null,
						$activeClass !== false && array_key_exists('active', $entry) ? $activeClass : null
					)
				);
				echo '><a';
				echo PET_Utility::BuildAttributesString(Utility::ArrayTake($entry, array('menu-id' => 'id', '_link' => 'href', 'menu-target' => 'target')));
				echo '>';
				if (array_key_exists('menu-title', $entry))
				{
					echo $entry['menu-title'];
				}
				echo '</a>';
				if (array_key_exists('_childs', $entry))
				{
					__EmitPaladioNavMenu(null, $itemClass, $selectedClass, $activeClass, $entry['_childs'], $index);
				}
				echo '</li>';
				$index++;
			}
			echo '</ul>';
		}
	}
	
	echo '<nav>';
	$index = 0;
	__EmitPaladioNavMenu
		(
			Utility::ArrayTake($_ELEMENT['attributes'], array('id', 'class')),
			$itemClass,
			$selectedClass,
			$activeClass,
			$entries,
			$index
		);
	echo '</nav>';
?>