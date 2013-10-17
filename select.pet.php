<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	echo '<select'.PET_Utility::BuildAttributesString(Utility::SubArray($_ELEMENT['attributes'], array('id', 'class', 'name'))).'>';
	if (array_key_exists('options', $_ELEMENT['attributes']))
	{
		if (array_key_exists('selected', $_ELEMENT['attributes']))
		{
			$selected = $_ELEMENT['attributes']['selected'];
		}
		else
		{
			$selected = null;
		}
		$data = PEN::Decode($_ELEMENT['attributes']['options']);
		if (!function_exists("__EmitPaladioSelect"))
		{
			function __EmitPaladioSelect($data, $selected)
			{
				foreach ($data as $option => $value)
				{
					if (is_array($value))
					{
						echo '<optgroup label="'.$option.'">';
						__EmitPaladioSelect($value, $selected);
						echo '</optgroup>';
					}
					else
					{
						if (!is_string($option))
						{
							$option = $value;
						}
						echo '<option value="'.$value.'"'.($value === $selected ? ' selected="selected"' : '').'>'.$option.'</option>';
					}
				}
			}
		}
		__EmitPaladioSelect($data, $selected);
	}
	echo '</select>';
?>