<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	echo '<select'.PET_Utility::BuildAttributesString(Utility::ArraySkip($_ELEMENT['attributes'], array('value', 'options'))).'>';
	if (array_key_exists('options', $_ELEMENT['attributes']))
	{
		if (array_key_exists('multiple', $_ELEMENT['attributes']) && array_key_exists('name', $_ELEMENT['attributes']))
		{
			$_ELEMENT['attributes']['name'] = String_Utility::EnsureEnd($_ELEMENT['attributes']['name'], '[]');
		}
		if (array_key_exists('value', $_ELEMENT['attributes']))
		{
			$value = PEN::Decode($_ELEMENT['attributes']['value']);
		}
		else
		{
			$value = null;
		}
		$data = PEN::Decode($_ELEMENT['attributes']['options']);
		if (!function_exists("__EmitPaladioSelect"))
		{
			function __EmitPaladioSelect($data, $selected)
			{
				foreach ($data as $option => $currentValue)
				{
					if (is_array($currentValue))
					{
						echo '<optgroup label="'.$option.'">';
						__EmitPaladioSelect($currentValue, $selected);
						echo '</optgroup>';
					}
					else
					{
						if (!is_string($option))
						{
							$option = $currentValue;
						}
						echo '<option value="'.$currentValue.'"';
						if ((is_array($value) && in_array($currentValue, $value)) || $value == $currentValue)
						{
							echo ' selected="selected"';
						}
						echo '>'.$option.'</option>';
					}
				}
			}
		}
		__EmitPaladioSelect($data, $value);
	}
	echo '</select>';
?>