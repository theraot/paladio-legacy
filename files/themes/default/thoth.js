/* thoth by Alfonso J. Ramos is licensed under a Creative Commons Attribution 4.0 International License. To view a copy of this license, visit http://creativecommons.org/licenses/by/4.0/ */

'use strict';

(	/* strings */
	function(thoth, window, undefined)
	{
		if (!('format' in String))
		{
			String.format = function(format)
			{
				var args = Array.prototype.slice.call(arguments, 1);
				return String.prototype.replace.call (
					format,
					/{(\d+)}/g,
					function(match, number)
					{
						return number in args ? args[number] : match;
					}
				);
			};
		}
		
		if (!('endsWith' in String.prototype))
		{
			String.prototype.endsWith = function (searchString, position)
			{
				position = position || this.length;
				position = position - searchString.length;
				var lastIndex = this.lastIndexOf(searchString);
				return lastIndex !== -1 && lastIndex === position;
			};
		}
		
		if (!('startsWith' in String.prototype))
		{
			String.prototype.startsWith = function (searchString, position)
			{
				position = position || 0;
				return this.indexOf(searchString, position) === position;
			};
		}
		
		if (!('trim' in String.prototype))
		{
			String.prototype.trim = function ()
			{
				return this.replace(/^\s+|\s+$/g, '');
			};
		}
	}
)(window.thoth = (window.thoth || {}), window);

(	/* arrays */
	function(thoth, window, undefined)
	{
		if (!('contains' in Array.prototype))
		{
			Array.prototype.contains = function (item)
			{
				var array = Object(this);
				var index = array.length >>> 0;
				while(index--)
				{
					if (index in array && array[index] === item)
					{
						return true;
					}
				}
				return false;
			};
		}
		
		if (!('containsWhere' in Array.prototype))
		{
			Array.prototype.containsWhere = function (predicate /*, thisArg*/)
			{
				if (typeof predicate !== 'function')
				{
					throw new TypeError();
				}
				var array = Object(this);
				var count = array.length >>> 0;
				var thisArg = arguments.lengths >= 2 ? arguments[1] : void 0;
				for (var index = 0; index < count; index++)
				{
					if (index in array && predicate.call(thisArg, array[index], index, array))
					{
						return true;
					}
				}
				return false;
			};
		}
		
		if (!('every' in Array.prototype))
		{
			Array.prototype.every = function(callback /*, thisArg*/)
			{
				if (typeof callback !== 'function')
				{
					throw new TypeError();
				}
				var array = Object(this);
				var count = array.length >>> 0;
				var thisArg = arguments.lengths >= 2 ? arguments[1] : void 0;
				for (var index = 0; index < count; index++)
				{
					if (index in array && !callback.call(thisArg, array[index], index, array))
					{
						return false;
					}
				}
				return true;
			};
		}
		
		if (!('filter' in Array.prototype))
		{
			Array.prototype.filter = function(callback /*, thisArg */)
			{
				if (typeof callback !== 'function')
				{
					throw new TypeError();
				}
				var array = Object(this);
				var count = array.length >>> 0;
				var result = [];
				var thisArg = arguments.length >= 2 ? arguments[1] : void 0;
				for (var index = 0; index < count; index++)
				{
					if (index in array)
					{
						var current = array[index];
						if (callback.call(thisArg, current, index, array))
						{
							result.push(current);
						}
					}
				}
				return result;
			};
		}
		
		if (!('forEach' in Array.prototype))
		{
			Array.prototype.forEach = function(callback /*, thisArg*/)
			{
				if (typeof callback !== 'function')
				{
					throw new TypeError();
				}
				var array = Object(this);
				var count = array.length >>> 0;
				var thisArg = arguments.lengths >= 2 ? arguments[1] : void 0;
				for (var index = 0; index < count; index++)
				{
					if (index in array)
					{
						callback.call(thisArg, array[index], index, array);
					}
				}
			};
		}
		
		if (!('indexOf' in Array.prototype))
		{
			Array.prototype.indexOf = function (item, fromIndex)
			{
				var array = Object(this);
				var count = array.length >>> 0;
				fromIndex = +fromIndex || 0;
				if (fromIndex < count)
				{
					if (fromIndex < 0)
					{
						fromIndex += count;
						if (fromIndex < 0)
						{
							fromIndex = 0;
						}
					}
					for (; fromIndex < count; fromIndex++)
					{
						if (fromIndex in array && array[fromIndex] === item)
						{
							return fromIndex;
						}
					}
				}
				return -1;
			};
		}

		if (!('lastIndexOf' in Array.prototype))
		{
			Array.prototype.lastIndexOf = function (item, fromIndex)
			{
				var array = Object(this);
				var count = array.length >>> 0;
				fromIndex = +fromIndex || count;
				if (fromIndex > count)
				{
					fromIndex = count;
				}
				if (fromIndex < 0)
				{
					fromIndex += count;
					if (fromIndex < 0)
					{
						return -1;
					}
				}
				while(fromIndex--)
				{
					if (fromIndex in array && array[fromIndex] === item)
					{
						return fromIndex;
					}
				}
				return -1;
			};
		}
		
		if (!('isArray' in Array))
		{
			Array.isArray = function(arg)
			{
				return typeof arg === 'object' && arg instanceof Array;
			};
		}
		
		if (!('remove' in Array.prototype))
		{
			Array.prototype.remove = function(item)
			{
				var array = Object(this);
				var count = array.length >>> 0;
				for (var index = 0; index < count; index++)
				{
					if (index in array && array[index] === item)
					{
						Array.prototype.splice.call(this, index, 1);
						return true;
					}
				}
				return false;
			};
		}
		
		if (!('removeAt' in Array.prototype))
		{
			Array.prototype.removeAt = function(key)
			{
				var array = Object(this);
				if (key in array)
				{
					Array.prototype.splice.call(this, key, 1);
					return true;
				} else {
					return false;
				}
			};
		}
		
		if (!('removeWhere' in Array.prototype))
		{
			Array.prototype.removeWhere = function(predicate /*, thisArg*/)
			{
				if (typeof predicate !== 'function')
				{
					throw new TypeError();
				}
				var array = Object(this);
				var result = 0;
				var count = array.length >>> 0;
				var thisArg = arguments.lengths >= 2 ? arguments[1] : void 0;
				for (var index = 0; index < count;)
				{
					if (index in array && predicate.call(thisArg, array[index], index, array))
					{
						Array.prototype.splice.call(this, index, 1);
						result++;
					} else {
						index++;
					}
				}
				return result;
			};

			thoth.invoke = function(callbacks /*, thisArg, args...*/)
			{
				var thisArg = arguments.length >= 2 ? arguments[1] : void 0;
				var args = Array.prototype.slice.call(arguments, 2, arguments.length);
				if (typeof callbacks === 'function')
				{
					callbacks.apply(thisArg, args);
				} else {
					var count = callbacks.length >>> 0;
					for (var index = 0; index < count; index++)
					{
						if (index in callbacks)
						{
							callbacks[index].apply(thisArg, args);
						}
					}
				}
			};

			thoth.invokeEx = function(callbacks, decisionCallback /*, thisArg, args...*/)
			{
				var thisArg = arguments.length >= 3 ? arguments[2] : void 0;
				var args = Array.prototype.slice.call(arguments, 3, arguments.length);
				if (typeof callbacks === 'function')
				{
					decisionCallback.call(thisArg, callbacks.apply(thisArg, args), -1);
				} else {
					var count = callbacks.length >>> 0;
					for (var index = 0; index < count; index++)
					{
						if (index in callbacks)
						{
							if (false === decisionCallback.call(thisArg, callbacks[index].apply(thisArg, args), index))
							{
								break;
							}
						}
					}
				}
			};
		}
	}
)(window.thoth = (window.thoth || {}), window);

( /* DOM manipulation */
	function (thoth, window, undefined)
	{
		function _addEventListener(element, eventName, handler)
		{
			if ('addEventListener' in element)
			{
				element.addEventListener(eventName, handler);
				return function(){element.removeEventListener(eventName, handler);};
			} else {
				eventName = 'on' + eventName;
				var _handler = function(event)
				{
					handler.call(element, event);
				};
				element.attachEvent(eventName, _handler);
				return function() { element.detachEvent('on' + eventName, _handler); };
			}
		}
		thoth.getInheritableAttribute = function (element, attribute)
		{
			do {
				if (typeof element === 'undefined' || element === null)
				{
					return undefined;
				}
				else if (!thoth.isElement(element))
				{
					element = element.parentNode;
				} else {
					if (thoth.hasAttribute(element, attribute))
					{
						return element.getAttribute(attribute);
					} else {
						element = element.parentNode;
					}
				}
			} while (true);
		};
		thoth.hasAttribute = function (element, attribute)
		{
			if ('hasAttribute' in element)
			{
				return element.hasAttribute(attribute);
			} else {
				return typeof element.getAttribute(attribute) === 'string' && typeof element[attribute] !== 'undefined';
			}
		};
		thoth.findParent = function (element, tagName)
		{
			tagName = tagName.toLowerCase();
			do {
				if (typeof element === 'undefined' || element === null)
				{
					return null;
				}
				else if (!thoth.isElement(element))
				{
					element = element.parentNode;
				} else {
					if (element.tagName.toLowerCase() === tagName)
					{
						return element;
					} else {
						element = element.parentNode;
					}
				}
			} while (true);
		};
		thoth.isElement = function (node)
		{
			if (typeof window.HTMLElement === 'object')
			{
				return node instanceof window.HTMLElement;
			} else {
				return typeof node === 'object' && node !== null && node.nodeType === 1;
			}
		};
		thoth.isNested = function (element, nest)
		{
			do {
				if (!thoth.isElement(element))
				{
					return false;
				}
				else if (element === nest)
				{
					return true;
				} else {
					element = element.parentNode;
				}
			} while (true);
		};
		thoth.prev = function (element)
		{
			if ('nextElementSibling' in element)
			{
				return element.previousElementSibling;
			} else {
				do {
					element = element.previousSibling;
				} while (!thoth.isElement(element));
				return element;
			}
		};
		thoth.next = function (element)
		{
			if ('nextElementSibling' in element)
			{
				return element.nextElementSibling;
			} else {
				do {
					element = element.nextSibling;
				} while (!thoth.isElement(element));
				return element;
			}
		};
		thoth.addClass = function(element, className)
		{
			if (thoth.isElement(element) && typeof className === 'string' && className != '')
			{
				if ('classList' in element)
				{
					element.classList.add(className);
				} else {
					if (!element.className.match(new RegExp('(\\s|^)' + className + '(\\s|$)')))
					{
						element.className += ' ' + className;
					}
				}
			}
		};
		thoth.removeClass = function(element, className)
		{
			if (thoth.isElement(element) && typeof className === 'string' && className != '')
			{
				if ('classList' in element)
				{
					element.classList.remove(className);
				} else {
					var pattern = new RegExp('(\\s|^)'+className+'(\\s|$)');
					element.className = element.className.replace(pattern, ' ');
				}
			}
		};
		thoth.hasClass = function(element, className)
		{
			if (thoth.isElement(element) && typeof className === 'string' && className != '')
			{
				if ('classList' in element)
				{
					return element.classList.contains(className);
				} else {
					return element.className.match(new RegExp('(\\s|^)'+className+'(\\s|$)'));
				}
			} else {
				return false;
			}
		};
		thoth.getClasses = function(element, className)
		{
			if (thoth.isElement(element) && typeof className === 'string' && className != '')
			{
				if ('classList' in element)
				{
					return element.classList;
				} else {
					return element.className.split(/\s/);
				}
			} else {
				return null;
			}
		};
		thoth.getValue = function(element)
		{
			var field_type = element.type.toLowerCase();
			switch (field_type)
			{
				case 'radio':
				case 'checkbox':
					return element.checked;
				case 'select-one':
					return element.options[element.selectedIndex];
				case 'select-multiple':
					var result = [];
					var index = element.options.length;
					while (index--)
					{
						var option = element.options[index];
						if (option.selected)
						{
							result.unshift(option.value);
						}
					}
					return result;
				case 'submit':
				case 'reset':
				case 'button':
				case 'image':
					return undefined;
				default:
					if ('value' in element)
					{
						return element.value;
					} else {
						return undefined;
					}
			}
		};
		thoth.getType = function(element)
		{
			var field_type = element.type.toLowerCase();
			if (field_type === 'text')
			{
				return element.getAttribute('type').toLowerCase();
			}
			else if (field_type === 'email')
			{
				if (thoth.hasAttribute(element, 'multiple'))
				{
					return 'email-multiple';
				} else {
					return 'email-one';
				}
			} else {
				return field_type;
			}
		};
		thoth.setValue = function(element, value)
		{
			var field_type = element.type.toLowerCase();
			switch (field_type)
			{
				case 'radio':
				case 'checkbox':
					element.checked = value;
				case 'select-one':
				case 'select-multiple':
					var index = element.options.length;
					var option;
					if (typeof value === 'string')
					{
						while (index--)
						{
							option = element.options[index];
							if (option.value === value)
							{
								option.selected = true;
								break;
							}
						}
					} else {
						while (index--)
						{
							option = element.options[index];
							if (value.indexOf(option.value) !==-1)
							{
								option.selected = true;
							}
						}
					}
				case 'submit':
				case 'reset':
				case 'button':
				case 'image':
					break;
				default:
					if ('value' in element)
					{
						element.value = value;
					}
			}
		};
		thoth.clearValue = function(element)
		{
			var field_type = element.type.toLowerCase();
			switch (field_type)
			{
				case 'radio':
				case 'checkbox':
					element.checked = false;
				case 'select-one':
				case 'select-multiple':
					element.selectedIndex = -1;
				case 'submit':
				case 'reset':
				case 'button':
				case 'image':
					break;
				default:
					if ('value' in element)
					{
						element.value = '';
					}
			}
		};
		thoth.measure = function (element)
		{
			if (thoth.isElement(element))
			{
				var result = {};
				result.left = 0;
				result.top = 0;
				result.width = element.offsetWidth;
				result.height = element.offsetHeight;
				do {
					result.left += element.offsetLeft;
					result.top += element.offsetTop;
					element = element.offsetParent;
				} while (thoth.isElement(element))
				return result;
			} else {
				return null;
			}
		};
		thoth.on = function(element, events, handler)
		{
			if (typeof handler === 'function')
			{
				if (Array.isArray(events))
				{
					var result = [];
					var index = events.length;
					while (index--)
					{
						result.unshift(_addEventListener(element, events[index], handler));
					}
					return result;
				} else {
					return _addEventListener(element, events, handler);
				}
			} else {
				return null;
			}
		};
		thoth.ready = function(callback)
		{
			if (typeof callback === 'function')
			{
				if (window.document.readyState === 'loaded' || window.document.readyState === 'complete')
				{
					callback();
				} else {
					if ('addEventListener' in window.document)
					{
						window.document.addEventListener('DOMContentLoaded', callback);
					} else {
						window.document.attachEvent (
							'onreadystatechange',
							function()
							{
								if (window.document.readyState === 'loaded' || window.document.readyState === 'complete')
								{
									if (callback !== null)
									{
										callback();
										callback = null;
									}
								}
							}
						);
					}
				}
			}
		};
	}
)(window.thoth = (window.thoth || {}), window);

( /* Form validation & Events */
	function (thoth, window, undefined)
	{
		thoth.VALIDATION_INVALID_ELEMENT = -1;
		thoth.VALIDATION_NOT_VALIDABLE = -2;
		thoth.VALIDATION_VALID = 0;
		thoth.VALIDATION_TYPE_MISMATCH = 1;
		thoth.VALIDATION_PATTERN_MISMATCH = 2;
		thoth.VALIDATION_UNDERFLOW = 3;
		thoth.VALIDATION_OVERFLOW = 4;
		thoth.VALIDATION_MISSING = 5;
		thoth.VALIDATION_STEP_MISMATCH = 6;
		thoth.VALIDATION_TOO_LONG = 7;
		thoth.VALIDATION_CUSTOM_FAILURE = 8;
		var months_31 = [1, 3, 5, 7, 8, 10, 12];
		var years_53 = [
			  4,   9,  15,  20,  26,  32,  37,  43,  48,
			 54,  60,  65,  71,  76,  82,  88,  93,  99,
			105, 111, 116, 122, 128, 133, 139, 144, 150,
			156, 161, 167, 172, 178, 184, 189, 195, 201,
			207, 212, 218, 224, 229, 235, 240, 246, 252,
			257, 263, 268, 274, 280, 285, 291, 296, 303,
			308, 314, 320, 325, 331, 336, 342, 348, 353,
			359, 364, 370, 376, 381, 387, 392, 398];
		var types_literal = [
			'text',
			'search',
			'email-one',
			'email-multiple',
			'password'
		];
		function processDate(value, type) {
			var parts = null;
			var success = false;
			var years, months, steps;
			switch (type)
			{
				case 'year':
					parts = value.match(/^([0-9]{4})$/);
					if (parts !== null)
					{
						success = window.parseInt(parts[1]) > 0;
						parts = [parts[1]];
					}
					break;
				case 'month':
					parts = value.match(/^([0-9]{4})-(1[12]|0[1-9])$/);
					if (parts !== null)
					{
						success = window.parseInt(parts[1]) > 0;
						parts = [parts[1], parts[2]];
					}
					break;
				case 'day':
				case 'date':
					parts = value.match(/^([0-9]{4})-(1[12]|0[1-9])-([0-9]{2})$/);
					if (parts !== null)
					{
						years = window.parseInt(parts[1]);
						months = window.parseInt(parts[2]);
						steps = window.parseInt(parts[3]);
						if (years > 0 && steps > 0 && steps < 32)
						{
							if (steps <= 28)
							{
								success = true;
							}
							else if (steps === 29)
							{
								if (months === 2)
								{
									success = years % 400 === 0 || (years % 4 === 0 && years % 100 === 0);
								} else {
									success = true;
								}
							}
							else if (steps === 30)
							{
								success = months !== 2;
							} else {
								success = months_31.indexOf(months) !== -1;
							}
						}
						parts = [parts[1], parts[2], parts[3]];
					}
					break;
				case 'week':
					parts = value.match(/^([0-9]{4})-W([0-9]{2})$/);
					if (parts !== null)
					{
						years = window.parseInt(parts[1]);
						steps = window.parseInt(parts[2]);
						if (years > 0 && steps > 0 && steps < 54)
						{
							if (steps <= 53)
							{
								return true;
							} else {
								return years_53.indexOf(years % 400) !== -1;
							}
						}
						parts = [parts[1], parts[2]];
					}
					break;
				case 'datetime':
				case 'datetime-local':
					var regex = '([0-9]{4}-[0-9]{2}-[0-9]{2})T([0-9]{2}:[0-9]{2}(?::[0-9]{2}(?:\.[0-9]{1,3})?)?)'; 
					if (type === 'datetime')
					{
						regex += 'Z';
					}
					parts = value.match('^' + regex + '$');
					if (parts !== null)
					{
						var date = processDate(parts[1], 'date');
						if (date !== null)
						{
							var time = processDate(parts[2], 'time');
							if (time !== null)
							{
								success = true;
								parts = [date[0], date[1], date[2], time[0], time[1], time[2], time[3]];
							}
						}
					}
					break;
				case 'time':
					parts = value.match(/^([01][0-9]|2[0-3]):([0-5][0-9])(?::([0-5][0-9])(?:\.([0-9]{1,3}))?)?$/);
					if (parts !== null)
					{
						success = true;
						if (typeof parts[3] === 'undefined')
						{
							parts[3] = '00';
						}
						if (typeof parts[4] === 'undefined')
						{
							parts[4] = '000';
						}
						if (parts[4].length === 2)
						{
							parts[4] += '00';
						}
						if (parts[4].length === 1)
						{
							parts[4] += '0';
						}
						parts = [parts[1], parts[2], parts[3], parts[4]];
					}
					break;
				default:
			}
			if (success)
			{
				return parts;
			} else {
				return null;
			}
		}
		function _validateField(field, validator)
		{
			var form = field.form;
			if (thoth.isElement(field))
			{
				var result = {};
				if (validator !== null)
				{
					field.validation = field.validation || {};
					field.validation.validator = validator;
					field.validation.revision = validator.getRevision();
					field.validation.result = result;
				}
				var type = thoth.getType(field);
				if (!field.willValidate)
				{
					// Not validable //
					return result.value = thoth.VALIDATION_NOT_VALIDABLE;
				}
				else if (type === 'select-one')
				{
					if (thoth.hasAttribute(field, 'required') && field.selectedIndex === -1)
					{
						return result.value = thoth.VALIDATION_MISSING;
					}
				}
				else if (type === 'select-multiple')
				{
					// Empty //
				} else {
					var value = thoth.getValue(field);
					if ('validation' in field)
					{
						if ('sanitation' in field.validation)
						{
							value = field.validation.sanitation(value);
						}
					}
					var name = field.name;
					if (type === 'radio')
					{
						if (name === null)
						{
							if (thoth.hasAttribute(field, 'required') && value !== true)
							{
								return result.value = thoth.VALIDATION_MISSING;
							}
						}
						else 
						{
							if (form != null)
							{
								var index = form.elements.length;
								// Discover group //
								if (validator !== null)
								{
									var required = false;
									var missing = true;
									while (index--)
									{
										if (form.elements[index].getAttribute('name') === name && thoth.getType(form.elements[index]) === type)
										{
											if (thoth.getValue(form.elements[index]) === true)
											{
												missing = false;
											}
											if (thoth.hasAttribute(form.elements[index], 'required'))
											{
												required = true;
											}
											form.elements[index].validation = form.elements[index].validation || {};
											form.elements[index].validation.validator = validator;
											form.elements[index].validation.revision = validator.getRevision();
											form.elements[index].validation.result = result;
										}
									}
									if (missing && required)
									{
										return result.value = thoth.VALIDATION_MISSING;
									}
								} else {
									while (index--)
									{
										if (form.elements[index].getAttribute('name') === name && thoth.getType(form.elements[index]) === type)
										{
											if (thoth.getValue(form.elements[index]) === true)
											{
												break;
											}
											else if (thoth.hasAttribute(form.elements[index], 'required'))
											{
												return result.value =thoth.VALIDATION_MISSING;
											}
										}
									}
								}
							} else {
								if (thoth.getValue(form.elements[index]) !== true && thoth.hasAttribute(field, 'required'))
								{
									return result.value = thoth.VALIDATION_MISSING;
								}
							}
						}
					}
					else if (type === 'checkbox')
					{
						if (thoth.hasAttribute(field, 'required') && value !== true)
						{
							return result.value = thoth.VALIDATION_MISSING;
						}
					} else {
						/*How to validate MIMETYPE of file?*/
						if (typeof value !== 'string' || value === '')
						{
							if (thoth.hasAttribute(field, 'required'))
							{
								return result.value = thoth.VALIDATION_MISSING;
							}
						} else {
							if (type in thoth.typeValidations)
							{
								var typevalidation = thoth.typeValidations[type];
								if (!typevalidation(value))
								{
									return result.value = result.value = thoth.VALIDATION_TYPE_MISMATCH;
								}
							}
							if (types_literal.indexOf(type))
							{
								if (thoth.hasAttribute(field, 'pattern'))
								{
									var pattern = field.getAttribute('pattern');
									if (!value.match(pattern))
									{
										return result.value = thoth.VALIDATION_PATTERN_MISMATCH;
									}
								}
								if (thoth.hasAttribute(field, 'maxlength'))
								{
									var maxlength = parseInt(field.getAttribute('maxlength'));
									if (!window.isNaN(maxlength) && value.length > maxlength)
									{
										return result.value = thoth.VALIDATION_TOO_LONG;
									}
								}
							} else {
								if (type == 'range' || type == 'number')
								{
									if (thoth.hasAttribute(field, 'max'))
									{
										var max = parseFloat(field.getAttribute('max'));
										if (!window.isNaN(max) && value > max)
										{
											return result.value = thoth.VALIDATION_OVERFLOW;
										}
									}
									if (thoth.hasAttribute(field, 'min'))
									{
										var min = parseFloat(field.getAttribute('min'));
										if (!window.isNaN(min) && value < min)
										{
											return result.value = thoth.VALIDATION_UNDERFLOW;
										}
										else if (thoth.hasAttribute(field, 'step'))
										{
											var step = parseFloat(field.getAttribute('step'));
											if (!window.isNaN(step) && ((value - min) / step) !== parseInt((value - min) / step))
											{
												return result.value = thoth.VALIDATION_STEP_MISMATCH;
											}
										}
									}
								}
								else if (type == 'date' || type == 'month' || type == 'week' || type == 'datetime' || type == 'datetime-local' || type == 'time')
								{
									if (thoth.hasAttribute(field, 'max'))
									{
										if (!window.isNaN(max) && value > field.getAttribute('max'))
										{
											return result.value = thoth.VALIDATION_OVERFLOW;
										}
									}
									if (thoth.hasAttribute(field, 'min'))
									{
										if (!window.isNaN(min) && value < field.getAttribute('min'))
										{
											return result.value = thoth.VALIDATION_UNDERFLOW;
										}
										/*else if (thoth.hasAttribute(field, 'step'))
										{
											// NOT IMPLEMENTED //
										}*/
									}
								}
							}
						}
					}
				}
				if (typeof value === 'string' && value !== '')
				{
					if (thoth.hasAttribute(field, 'data-validate'))
					{
						var customValidation = field.getAttribute('data-validate').split(/\s/);
						var validationIndex = customValidation.length;
						while (validationIndex--)
						{
							var data = customValidation[validationIndex].match(/^([^\(]+)(?:\(([^\)]*)\))?$/);
							if (data[1] in thoth.customValidations)
							{
								if (typeof data[2] !== 'undefined')
								{
									if ((data[2].charAt(0) === '"' && data[2].endssWith('"')) || (data[2].charAt(0) === '\'' && data[2].endssWith('\'')))
									{
										data[2] = data[2].substr(1, data[2].length - 1);
									} else {
										var fields = null;
										var _param = data[2].substr(1);
										if (data[2].charAt(0) === '&')
										{
											fields = thoth.findFieldsByName(form, _param);
										}
										else if (data[2].charAt(0) === '@')
										{
											field = form.querySelector(_param);
										}
										else if (data[2].charAt(0) === '$')
										{
											data[2] = field.getAttribute(_param);
										}
										if (fields !== null)
										{
											if (fields.length > 0)
											{
												data[2] = thoth.getValue(fields[0]);
											} else {
												data[2] = undefined;
											}
										}
									}
								}
								if (!thoth.customValidations[data[1]](value, data[2]))
								{
									return result.value = thoth.VALIDATION_CUSTOM_FAILURE;
								}
							}
						}
					}
				}
				return result.value = thoth.VALIDATION_VALID;
			} else {
				return thoth.VALIDATION_INVALID_ELEMENT;
			}
		}
		thoth.customValidations = {
			'single-line': function(val) { return val.match(/\r|\n/) === null; },
			'numeric': function(val) { return val.match(/^-?[0-9]*\.?[0-9]+(?:[eE][-+]?[0-9]+)?$/) !== null; },
			'integer': function(val) { return val.match(/^-?[0-9]+$/) !== null; },
			'decimal': function(val) { return val.match(/^-?[0-9]*\.?[0-9]+$/) !== null; },
			'alpha': function(val) { return val.match(/^[a-zA-Z]+$/) !== null; },
			'alphanumeric': function(val) { return val.match(/^[a-zA-Z0-9]+$/) !== null; },
			'base2': function(val) { return val.match(/^[01]+$/) !== null; },
			'base8': function(val) { return val.match(/^[0-7]+$/) !== null; },
			'base16': function(val) { return val.match(/^[a-fA-F0-9]+$/) !== null; },
			'base64': function(val) { return val.match(/^(?:[A-Za-z0-9+/]{4})*(?:[A-Za-z0-9+/]{2}==|[A-Za-z0-9+/]{3}=)?$/) !== null; },
			'base2-encoded': function(val) { return val.match(/^(?:[01]{8})+$/) !== null; },
			'base16-encoded': function(val) { return val.match(/^(?:[a-fA-F0-9]{2})+$/) !== null; },
			'base64-encoded': function(val) { return val.match(/^(?:[A-Za-z0-9+/]{4})*(?:[A-Za-z0-9+/]{2}==|[A-Za-z0-9+/]{3}=)?$/) !== null; },
			'domain': function(val) { return val.match(/^(?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/) !== null; },
			'ipv4': function(val) {return val.match(/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/) !== null; },
			'min-length': function(val, arg) { return val.length >= arg; },
			'max-length': function(val, arg) { return val.length <= arg; },
			'exact-length': function(val, arg) { return val.length === arg; },
			'greater_than': function(val, arg) { return val > arg; },
			'less_than': function(val, arg) { return val < arg; },
			'equals': function(val, arg) { return val === arg; },
			'match': function(val, arg) { return val.match(arg) !== null; },
		};
		thoth.typeValidations = {
			'tel': thoth.customValidations['single-line'],
			'url': function(val)
						{
							var url_unit = '(?:[a-zA-Z0-9\\$\\(\\)\\*\\+\\-\\.\\?!&\',/:;=@_~]|%[a-fA-F0-9]{2})';
							var url_unit_q = '(?:[a-zA-Z0-9\\$\\(\\)\\*\\+\\-\\.!&\',;=_~]|%[a-fA-F0-9]{2})';
							var url_unit_r = '(?:[a-zA-Z0-9\\$\\(\\)\\*\\+\\-\\.\\?!&\',;=_~]|%[a-fA-F0-9]{2})';
							var url_unit_s = '(?:[a-zA-Z0-9\\$\\(\\)\\*\\+\\-\\.!&\',:;=@_~]|%[a-fA-F0-9]{2})';
							var schema = '(?:[a-zA-Z][a-zA-Z0-9+-.]*)';
							var username = '(?:' + url_unit_q + '*(?:\\:(?:' + url_unit_q + '*))?)';
							var password = '(?:' + url_unit_r + '*(?:\\:(?:' + url_unit_r + '*))?)';
							var userinfo = '(?:' + username + '(?:@' + password + ')?@)?';
							var port = '(?:\\:[0-9]*)?';
							var path = '(?:/' + url_unit_s + '+(?:/' + url_unit_s + '+)*)?'; // not allowing empty path segments //
							var query = '(?:\\\?(?:' + url_unit + '*))?';
							var regex = '^' + schema + '\\://' + userinfo + '([^:/]+)' + port + path + query +'$';
							var matches = val.match(regex);
							if (matches === null)
							{
								return false;
							} else {
								var domain = matches[1];
								return thoth.customValidations['domain'](domain);
							}
						},
			'email-one': function(val)
						{
							var matches = val.match(/^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@([^:/]+)$/);
							if (matches === null)
							{
								return false;
							} else {
								var domain = matches[1];
								return thoth.customValidations['domain'](domain);
							}
						},
			'email-multiple': function(val)
						{
							var emails = val.split(',');
							var email_validation = thoth.typeValidations['email-one'];
							var index = emails.length;
							while (index--)
							{
								if (!email_validation(emails[index]))
								{
									return false;
								}
							}
							return true;
						},
			'color' : function(val) { return val.match(/^#[0-9A-Fa-f]{6}$/) !== null; },
			'number': thoth.customValidations['numeric'],
			'range': thoth.customValidations['numeric'],
			'month': function(val) { return processDate(val, 'month') !== null; },
			'date': function(val) { return processDate(val, 'date') !== null; },
			'week': function(val) { return processDate(val, 'week') !== null; },
			'datetime': function(val) { return processDate(val, 'datetime') !== null; },
			'datetime-local': function(val) { return processDate(val, 'datetime-local') !== null; },
			'time': function(val) { return processDate(val, 'time') !== null; },
		};
		thoth.findFormByName = function(form)
		{
			if (typeof form === 'string')
			{
				return window.document.forms[form];
			} else {
				if (thoth.isElement(form))
				{
					return form;
				} else {
					return null;
				}
			}
		};
		thoth.parseDate = function(value, type)
		{
			var data = processDate(value, type);
			var result = new Date(NaN);
			switch (type)
			{
				case 'year':
					result.setFullYear(data[0], 0, 0);
					break;
				case 'month':
					result.setFullYear(window.parseInt(data[0]), window.parseInt(data[1]) - 1, 0);
					break;
				case 'day':
				case 'date':
					result.setFullYear(window.parseInt(data[0]), window.parseInt(data[1]) - 1, window.parseInt(data[2]));
					break;
				case 'week':
					result.setFullYear(window.parseInt(data[0]), 0, 1);
					var day = result.getDay();
					day = (day === 0) ? 7 : day;
					var offset = -day + 1;
					if (offset < -3)
					{
						offset += 7;
					}
					result.setTime(result.getTime() + ((window.parseInt(data[1]) - 1) * 7 + offset) * 24 * 60 * 60 * 1000);
					break;
				case 'datetime':
					result = new Date(Date.UTC(
						window.parseInt(data[0]),		/*year*/
						window.parseInt(data[1]) - 1,	/*month*/
						window.parseInt(data[2]),		/*day*/
						window.parseInt(data[3]),		/*hour*/
						window.parseInt(data[4]),		/*minute*/
						window.parseInt(data[5]),		/*second*/
						window.parseInt(data[6])		/*fraction*/
					));
					break;
				case 'datetime-local':
					result = new Date(
						window.parseInt(data[0]),		/*year*/
						window.parseInt(data[1]) - 1,	/*month*/
						window.parseInt(data[2]),		/*day*/
						window.parseInt(data[3]),		/*hour*/
						window.parseInt(data[4]),		/*minute*/
						window.parseInt(data[5]),		/*second*/
						window.parseInt(data[6])		/*fraction*/
					);
					break;
				case 'time':
					result.setTime(((window.parseInt(data[0]) * 60 +  window.parseInt(data[1])) * 60 + window.parseInt(data[2])) * 1000 + window.parseInt(data[3]));
					break;
				default:
			}
			return result;
		};
		thoth.validateField = function(field)
		{
			return _validateField(field, null);
		};
		thoth.FormValidator = function (form)
		{
			form = thoth.findFormByName(form);
			this.form = form;
			if ('validator' in form)
			{
				throw new TypeError();
			}
			if (this.form === null)
			{
				throw new TypeError();
			}
			form.validator = this;
			var revision = 0;
			var _this = this;
			this.validatedHandlers = [];
			this.submitHandlers = [];
			this.fields = [];
			this.validClass = form.getAttribute('data-valid-class');
			this.invalidClass = form.getAttribute('data-invalid-class');
			this.validatingClass = form.getAttribute('data-validating-class');
			this.enabled = !thoth.hasAttribute(form, 'novalidate');
			form.setAttribute('novalidate', 'novalidate');
			var validateField = function(field)
			{
				if (!('validation' in field) || field.validation.revision != revision)
				{
					_validateField(field, _this);
				}
				var result = field.validation.result.value;
				thoth.removeClass(field, _this.validatingClass);
				if (result > 0)
				{
					thoth.addClass(field, _this.invalidClass);
					thoth.removeClass(field, _this.validClass);
				}
				else if (result === 0)
				{
					thoth.addClass(field, _this.validClass);
					thoth.removeClass(field, _this.invalidClass);
				}
				return result;
			};
			this.getRevision = function ()
			{
				return revision;
			};
			this.addSanitation = function(type, sanitation)
			{
				var elements = form.elements;
				var index = elements.length;
				while (index--)
				{
					if (thoth.getType(elements[index]) === type)
					{
						elements[index].validation = elements[index].validation | {};
						elements[index].validation.sanitation = sanitation;
					}
				}
			};
			this.apply = function(type, callback)
			{
				var elements = form.elements;
				var index = elements.length;
				while (index--)
				{
					if (thoth.getType(elements[index]) === type)
					{
						callback(elements[index]);
					}
				}
			};
			this.validateForm = function()
			{
				var errors = [];
				revision++;
				var elements = form.elements;
				var index = elements.length;
				while (index--)
				{
					var result = validateField(elements[index]);
					if (result > 0)
					{
						errors.unshift({element: elements[index], result: result});
					}
				}
				return errors;
			};
			var submitHandler = function(event)
			{
				event = event || {};
				_triggerEvent(_this.submitHandlers, event);
				if (_this.enabled)
				{
					var index = form.elements.length;
					while (index--)
					{
						thoth.removeClass(form.elements[index], _this.invalidClass);
						thoth.removeClass(form.elements[index], _this.validatingClass);
						if (form.elements[index].willValidate)
						{
							thoth.addClass(form.elements[index], _this.validClass);
						} else {
							thoth.removeClass(form.elements[index], _this.validClass);
						}
					}
					event.errors = _this.validateForm();
					_triggerEvent(_this.validatedHandlers, event);
					index = event.errors.length;
					while (index--)
					{
						thoth.addClass(event.errors[index].element, _this.invalidClass);
						thoth.removeClass(event.errors[index].element, _this.validClass);
					}
					if (Array.isArray(event.errors) && event.errors.length > 0)
					{
						if ('preventDefault' in event)
						{
							event.preventDefault();
							return undefined;
						} else {
							event.returnValue = false;
							return false;
						}
					}
				}
				return true;
			};
			thoth.on (form, 'submit', submitHandler);
			var _index = form.elements.length;
			while (_index--)
			{
				var preValidation = function()
				{
					thoth.removeClass(this, _this.invalidClass);
					thoth.removeClass(this, _this.validClass);
					if (this.willValidate)
					{
						thoth.addClass(this, _this.validatingClass);
					}
				};
				var validate = function()
				{
					if (this.willValidate)
					{
						revision++;
						validateField(this);
					}
				};
				if (!('willValidate' in form.elements[_index]))
				{
					Object.defineProperty (
						form.elements[_index],
						'willValidate',
						{
							get: function()
							{
								var type = thoth.getType(this);
								return (type !== 'hidden' && type !== 'image' && type !== 'submit' && type !== 'reset' && type !== 'button' && type !== 'keygen' && !thoth.hasAttribute(this, 'readonly') && !thoth.hasAttribute(this, 'disabled'));
							}
						}
					);
				}
				var trigger = form.elements[_index].getAttribute('data-validation-trigger') || 'blur';
				trigger = trigger.split(/\s/);
				thoth.on (form.elements[_index], ['reset', 'change', 'input', 'keyup'], preValidation);
				thoth.on (form.elements[_index], trigger, validate);
			}
			this.addEventListener = function(eventName, handler)
			{
				if (eventName === 'submit')
				{
					this.submitHandlers.push(handler);
				}
				else if (eventName === 'validated')
				{
					this.validatedHandlers.push(handler);
				}
			};
			this.removeEventListener = function(eventName, handler)
			{
				if (eventName === 'submit')
				{
					this.submitHandlers.remove(handler);
				}
				else if (eventName === 'validated')
				{
					this.validatedHandlers.remove(handler);
				}
			};
		};
		thoth.findFieldsByType = function (form, type)
		{
			var result = [];
			var elements = form.elements;
			var index = elements.length;
			while (index--)
			{
				var item = elements[index];
				if (thoth.getType(item) === type)
				{
					result.unshift(item);
				}
			}
			return result;
		};
		thoth.findFieldsByName = function (form, name)
		{
			var result = [];
			var elements = form.elements;
			var index = elements.length;
			while (index--)
			{
				var item = elements[index];
				if (item.name === name)
				{
					result.unshift(item);
				}
			}
			return result;
		};
		thoth.clearForm = function (form)
		{
			var elements = form.elements;
			var index = elements.length;
			while (index--)
			{
				thoth.clearValue(elements[index]);
			}
		};
		thoth.saveForm = function (form)
		{
			var data = {};
			var elements = form.elements;
			var index = elements.length;
			while (index--)
			{
				var element = elements[index];
				var value = thoth.getValue(element);
				if (element.name in data)
				{
					data[element.name].push(value);
				} else {
					data[element.name] = [value];
				}
			}
			return data;
		};
		thoth.loadForm = function (form, data)
		{
			var elements = form.elements;
			var count = {};
			var index = elements.length;
			while (index--)
			{
				var element = elements[index];
				if (element.name in data)
				{
					var subindex = 0;
					if (element.name in count)
					{
						subindex = count[element.name];
					} else {
						count[element.name] = subindex;
					}
					thoth.setValue(element, data[element.name][subindex]);
					count[element.name]++;
				}
			}
		};
		//----
		var _events = {};
		function _triggerEvent(handlers, event)
		{
			var go = true;
			if ('stopImmediatePropagation' in event)
			{
				event.stopImmediatePropagation = function()
				{
					go = false;
					event.stopImmediatePropagation();
				};
			} else {
				event.stopImmediatePropagation = function()
				{
					go = false;
				};
			}
			thoth.invokeEx(handlers, function() { return go; }, thoth, event);
		}
		thoth.addEventListener = function(eventName, handler)
		{
			if (!(eventName in _events))
			{
				_events[eventName] = [];
			}
			_events[eventName].push(handler);
		};
		thoth.removeEventListener = function(eventName, handler)
		{
			if (eventName in _events)
			{
				_events[eventName].remove(handler);
			}
		};
		thoth.triggerEvent = function(eventName, event)
		{
			if (eventName in _events)
			{
				event = event || {};
				_triggerEvent(_events[eventName], event);
			}
			return event.returnValue;
		};
	}
)(window.thoth = (window.thoth || {}), window);

(	/* delay */
	function(thoth, window, undefined)
	{
		var delayed_operations = [];
		var free_delayed_ids = [0];
		var used_delayed_ids = [];
		
		function _get_delayed_id()
		{
			var id = free_delayed_ids.pop();
			used_delayed_ids.push(id);
			if (free_delayed_ids.length === 0)
			{
				free_delayed_ids.push(used_delayed_ids.length);
			}
			return id;
		};
		
		function _free_delayed_id(id)
		{
			if (used_delayed_ids.remove(id))
			{
				free_delayed_ids.push(id);
			}
		};
		
		function _run_delayed(id)
		{
			var delayed_operation;
			var index = delayed_operations.length;
			while (index--)
			{
				delayed_operation = delayed_operations[index];
				if (delayed_operation.id === id)
				{
					window.clearTimeout(delayed_operation.timeout_id);
					var operation = delayed_operation.operation;
					if (typeof delayed_operation.repeat === 'number')
					{
						delayed_operation.repeat--;
						if (delayed_operation.repeat === 0)
						{
							delayed_operation.repeat = false;
						}
					}
					if (delayed_operation.repeat !== false)
					{
						var timeout_id = window.setTimeout(function(){_run_delayed(id);}, delayed_operation.delay);
						delayed_operation.timeout_id = timeout_id;
					} else {
						if (delayed_operation.done !== null)
						{
							delayed_operation.done();
						}
						delayed_operations.splice(index, 1);
						_free_delayed_id(id);
					}
					operation();
					return true;
				}
			}
			return false;
		};
		
		//--------------------------------------------------------------
		
		thoth.delay = function(operation, delay, repeat, done)
		{
			var id = _get_delayed_id();
			var _repeat;
			if (typeof repeat === 'number')
			{
				_repeat = repeat;
			}
			else if (typeof repeat !== 'undefined' && repeat)
			{
				_repeat = true;
			} else {
				_repeat = false;
			}
			var _done;
			if (typeof done === 'function')
			{
				_done = done;
			} else {
				_done = null;
			}
		    var delayed_operation =
		    {
		        operation: operation,
		        id: id,
		        delay: delay,
		        done: _done,
		        repeat: _repeat };
			delayed_operations.push(delayed_operation);
			var timeout_id = window.setTimeout(function(){_run_delayed(id);}, delay);
			delayed_operation.timeout_id = timeout_id;
			return id;
		};
		
		thoth.stop = function (id)
		{
			var delayed_operation;
			var index = delayed_operations.length;
			while (index--)
			{
				delayed_operation = delayed_operations[index];
				if (delayed_operation.id === id)
				{
					var timeout_id = delayed_operation.timeout_id;
					delayed_operations.splice(index, 1);
					_free_delayed_id(id);
					window.clearTimeout(timeout_id);
					return true;
				}
			}
			return false;
		};
		
		thoth.isRunning = function (id)
		{
			var delayed_operation;
			var index = delayed_operations.length;
			while (index--)
			{
				delayed_operation = delayed_operations[index];
				if (delayed_operation.id === id)
				{
					return true;
				}
			}
			return false;
		};
	}
)(window.thoth = (window.thoth || {}), window);

(	/* Dictionary */
	function(thoth, window, undefined)
	{
		thoth.Dictionary = function()
		{
			var dic = {};
			var length = 0;
			
			//----------------------------------------------------------
			
			this.contains = function (value)
			{
				for (var key in dic)
				{
					if (dic[key] === value)
					{
						return true;
					}
				}
				return false;
			};
			
			this.containsKey = function (key)
			{
				if (key in dic)
				{
					return true;
				} else {
					return false;
				}
			};
			
			this.containsWhere = function (predicate)
			{
				for (var key in dic)
				{
					if (predicate(dic[key]))
					{
						return true;
					}
				}
				return false;
			};
			
			this.every = function(callback)
			{
				for (var key in dic)
				{
					if (!callback(dic[key]))
					{
						return false;
					}
				}
				return true;
			};
			
			this.forEach = function(callback)
			{
				for (var key in dic)
				{
					callback(dic[key]);
				}
			};
			
			this.get = function (key)
			{
				if (key in dic)
				{
					return dic[key];
				} else {
					return undefined;
				}
			};
			
			this.length = function()
			{
				return length;
			};
			
			this.remove = function (key)
			{
				if (key in dic)
				{
					var result = dic[key];
					delete dic[key];
					length--;
					return result;
				} else {
					return undefined;
				}
			};
			
			this.removeWhere = function (predicate)
			{
				var result = 0;
				for (var key in dic)
				{
					if (predicate(dic[key]))
					{
						delete dic[key];
						result++;
						length--;
					}
				}
				return result;
			};
			
			this.set = function(key, item)
			{
				if (!(key in dic))
				{
					length++;
				}
				dic[key] = item;
			};
			
			this.toString = function()
			{
				var result = '';
				var first = true;
				for (var key in dic)
				{
					if (first)
					{
						first = false;
					} else {
						result += ',';
					}
					result += key;
				}
				return result;
			};
		};
	}
)(window.thoth = (window.thoth || {}), window);

(	/* Dispatch */
	function(thoth, window, undefined)
	{
		thoth.Dispatch = function()
		{
			var events = new thoth.Dictionary();
			
			function _execute_event(event)
			{
				event.executing = true;
				_execute_event_continue(event);
			}
			
			function _execute_event_continue(event)
			{
				if (event.executing)
				{
					var callin = function()
					{
						step(event);
					};
					var step = function(_event)
					{
						var continuations = _event.continuations;
						if (continuations.length > 0)
						{
							var continuation = _event.continuations.shift();
							continuation();
							thoth.delay(callin, 0, false);
						} else {
							_event.executing = false;
							events.remove(_event.id);
						}
					};
					thoth.delay(callin, 0, false);
				}
			}
			
			function _append_event(id, continuation)
			{
				var event = events.get(id);
				if (typeof event === 'undefined' || event === null)
				{
					return false;
				} else {
					event.continuations.push(continuation);
					return true;
				}
			};
			
			//----------------------------------------------------------
			
			this.add = function(id, continuation)
			{
				if (!_append_event(id, continuation))
				{
					events.set (
						id,
						{
							id : id,
							continuations : [continuation],
							executing : false }
					);
				}
				return true;
			};
			
			this.containsKey = function(id)
			{
				return events.containsKey(id);
			};
			
			this.go = function (id)
			{
				var event = events.get(id);
				if (typeof event === 'undefined')
				{
					return false;
				} else {
					_execute_event(event);
					return true;
				}
			};
			
			this.remove = function (id)
			{
				return events.remove(id);
			};
			
			this.stop = function (id)
			{
				var event = events.get(id);
				if (typeof event === 'undefined' || event === null)
				{
					return false;
				} else {
					event.executing = false;
					events.remove(event.id);
					return true;
				}
			};
		};
		
		thoth.Dispatch.global = new thoth.Dispatch();
	}
)(window.thoth = (window.thoth || {}), window);

(	/* include */
	function(thoth, window, undefined)
	{
		var SEPARATOR = '/';
		var _root_urls = [];
		var included_urls = [];
		var loading = new thoth.Dispatch();
		
		//--------------------------------------------------------------
		
		function _process_url(folders)
		{
			var result =  [];
			var folder;
			while (typeof (folder = folders.shift()) !== 'undefined')
			{
				if (folder === '.')
				{
					continue;
				}
				if (folder === '..')
				{
					result.pop();
					continue;
				}
				result.push(folder);
			}
			return result;
		}
		
		function _process_absolute_url(absolute)
		{
			if (typeof absolute !== 'string')
			{
				return [];
			} else {
				if (absolute.endsWith(SEPARATOR))
				{
					absolute = absolute.substr(0, absolute.length - SEPARATOR.length);
				}
				var folders = absolute.split(SEPARATOR);
				return _process_url(folders);
			}
		}
		
		function _process_relative_url(relative)
		{
			if (typeof relative !== 'string')
			{
				return ['.'];
			} else {
				if (relative.startsWith(SEPARATOR))
				{
					relative = relative.substr(SEPARATOR.length);
				}
				if (relative === '')
				{
					return ['.'];
				} else {
					var folders = relative.split(SEPARATOR);
					return _process_url(folders);
				}
			}
		}
		
		function _resolve_url(url, callback)
		{
			var check = url.indexOf('/');
			if (check !== -1 && check === url.indexOf('//'))
			{
				// take as absolute or protocol relative //
				callback(url);
			} else {
				var index = 0;
				var step = function()
				{
					if (index < _root_urls.length)
					{
						var test = _root_urls[index]; index++;
						var _url = thoth.resolve_relative_url(test, url);
						if (included_urls.contains(_url))
						{
							callback(_url);
						} else {
							thoth.url_exists (
								_url,
								function (exists)
								{
									if (exists)
									{
										callback(_url);
									} else {
										thoth.delay(step, 0, false);
									}
								}
							);
						}
					}
				};
				step();
			}
		}
		
		function _include(url, callback, once)
		{
			var document = window.document;
			var head = document.getElementsByTagName('head')[0] || document.documentElement;
			var script = document.createElement('script');
			var done = false;
			if (typeof callback === 'function')
			{
				script.onload = script.onreadystatechange = function()
				{
					if (!done && (!this.readyState || this.readyState === 'loaded' || this.readyState === 'interactive' || this.readyState === 'complete'))
					{
						done = true;
						var data = this.getAttribute('data-src');
						loading.go(data);
						loading.remove(data);
						this.onload = this.onreadystatechange = null;
						if (head && script.parentNode)
						{
							head.removeChild(script);
						}
					}
				};
				_resolve_url (
					url,
					function (resolved_url)
					{
						var insert = false;
						if (included_urls.contains(resolved_url))
						{
							if (once)
							{
								if (loading.containsKey(resolved_url))
								{
									loading.add(resolved_url, callback);
								} else {
									callback();
								}
							} else {
								insert = true;
							}
						} else {
							insert = true;
							included_urls.push(resolved_url);
						}
						if (insert)
						{
							script.type = 'text/javascript';
							script.async = true;
							script.src = resolved_url;
							script.setAttribute('data-src', resolved_url);
							loading.add(resolved_url, callback);
							head.insertBefore(script, head.firstChild);
						}
					}
				);
			}
		};
		
		//--------------------------------------------------------------
		
		thoth.include = function(url, callback)
		{
			if (typeof url === 'string')
			{
				_include(url, callback, false);
			}
			else if (Array.isArray(url))
			{
				var go = function()
				{
					if (url.length > 0)
					{
						var _url = url.shift();
						_include(_url, function(){thoth.delay(go, 0, false);}, false);
					} else {
						callback();
					}
				};
				thoth.delay(go, 0, false);
			}
		};
		
		thoth.include_once = function(url, callback)
		{
			if (typeof url === 'string')
			{
				if (!included_urls.contains(url))
				{
					_include(url, callback, true);
				}
			}
			else if (Array.isArray(url))
			{
				var go = function()
				{
					if (url.length > 0)
					{
						var _url = url.shift();
						if (!included_urls.contains(_url))
						{
							_include(_url, function(){thoth.delay(go, 0, false);}, true);
						} else {
							thoth.delay(go, 0, false);
						}
					} else {
						callback();
					}
				};
				thoth.delay(go, 0, false);
			}
		};
		
		thoth.configure = function(root_urls)
		{
			_root_urls = root_urls;
		};
		
		thoth.url_exists = function (url, callback)
		{
			var http = new window.XMLHttpRequest();
			http.onreadystatechange = function() 
			{
				if (http.readyState === 4)
				{
					http.onreadystatechange = null;
					if (callback !== null)
					{
						callback (http.status != 404);
						callback = null;
					}
				}
			};
			http.open('HEAD', url, true);
			http.send();
		};
		
		thoth.resolve_relative_url = function (absolute, relative)
		{
			absolute = _process_absolute_url(absolute);
			relative = _process_relative_url(relative);
			var result = _process_url(absolute.concat(relative));
			return result.join(SEPARATOR);
		};
		
		//--------------------------------------------------------------
		
		window.include = thoth.include;
		window.include_once = thoth.include_once;
	}
)(window.thoth = (window.thoth || {}), window);