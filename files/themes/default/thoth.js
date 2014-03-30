/* thoth by Alfonso J. Ramos is licensed under a Creative Commons Attribution 3.0 Unported License. Based on a work at github.com. To view a copy of this license, visit http://creativecommons.org/licenses/by/3.0/ */

'use strict';

(	/* strings */
	function(thoth, window, undefined)
	{
		if (!("format" in String))
		{
			String.format = function(format)
			{
				if (this === void 0 || this === null)
				{
					throw new TypeError();
				}
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
		
		if (!("endsWith" in String.prototype))
		{
			Object.defineProperty (
				String.prototype,
				'endsWith',
				{
					enumerable: false,
					configurable: false,
					writable: false,
					value: function (searchString, position)
					{
						position = position || this.length;
						position = position - searchString.length;
						var lastIndex = this.lastIndexOf(searchString);
						return lastIndex !== -1 && lastIndex === position;
					}
				}
			);
		}
		
		if (!("startsWith" in String.prototype))
		{
			Object.defineProperty (
				String.prototype,
				'startsWith',
				{
					enumerable: false,
					configurable: false,
					writable: false,
					value: function (searchString, position)
					{
						position = position || 0;
						return this.indexOf(searchString, position) === position;
					}
				}
			);
		}
	}
)(window.thoth = (window.thoth || {}), window);

(	/* arrays */
	function(thoth, window, undefined)
	{
		if (!("contains" in Array.prototype))
		{
			Array.prototype.contains = function (item)
			{
				if (this === void 0 || this === null)
				{
					throw new TypeError();
				}
				var array = Object(this);
				var count = array.length >>> 0;
				for (var index = 0; index < count; index++)
				{
					if (index in array && array[index] === item)
					{
						return true;
					}
				}
				return false;
			};
		}
		
		if (!("containsWhere" in Array.prototype))
		{
			Array.prototype.containsWhere = function (predicate /*, thisArg*/)
			{
				if (this === void 0 || this === null || typeof predicate !== "function")
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
			}
		}
		
		if (!("every" in Array.prototype))
		{
			Array.prototype.every = function(callback /*, thisArg*/)
			{
				if (this === void 0 || this === null || typeof callback !== "function")
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
		
		if (!("filter" in Array.prototype))
		{
			Array.prototype.filter = function(callback /*, thisArg */)
			{
				if (this === void 0 || this === null || typeof callback !== "function")
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
						var current = array[index]
						if (callback.call(thisArg, current, index, array))
						{
							result[result.length] = current;
						}
					}
				}
				return result;
			};
		}
		
		if (!("forEach" in Array.prototype))
		{
			Array.prototype.forEach = function(callback /*, thisArg*/)
			{
				if (this === void 0 || this === null || typeof callback !== "function")
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
		
		if (!("indexOf" in Array.prototype))
		{
			Array.prototype.indexOf = function (item, fromIndex)
			{
				if (this === void 0 || this === null)
				{
					throw new TypeError();
				}
				var array = Object(this);
				var count = array.length >>> 0;
				fromIndex = +fromIndex || 0;
				if (fromIndex < count)
				{
					if (fromIndex < 0)
					{
						fromIndex += length;
						if (fromIndex < 0)
						{
							fromIndex = 0;
						}
					}
					for (var index = fromIndex; index < count; index++)
					{
						if (index in array && array[index] === item)
						{
							return index;
						}
					}
				}
				return -1;
			};
		}
		
		if (!("isArray" in Array))
		{
			Array.isArray = function(arg)
			{
				return typeof array === 'object' && array instanceof Array;
			}
		}
		
		if (!("remove" in Array.prototype))
		{
			Array.prototype.remove = function(item)
			{
				if (this === void 0 || this === null)
				{
					throw new TypeError();
				}
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
		
		if (!("removeAt" in Array.prototype))
		{
			Array.prototype.removeAt = function(key)
			{
				if (this === void 0 || this === null)
				{
					throw new TypeError();
				}
				var array = Object(this);
				if (key in array)
				{
					Array.prototype.splice.call(this, key, 1);
					return true;
				}
				else
				{
					return false;
				}
			};
		}
		
		if (!("removeWhere" in Array.prototype))
		{
			Array.prototype.removeWhere = function(predicate /*, thisArg*/)
			{
				if (this === void 0 || this === null || typeof predicate !== "function")
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
					}
					else
					{
						index++
					}
				}
				return result;
			};
		}
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
			var count = delayed_operations.length;
			var delayed_operation;
			for (var index = 0; index < count; index++)
			{
				delayed_operation = delayed_operations[index];
				if (delayed_operation.id === id)
				{
					clearTimeout(delayed_operation.timeout_id);
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
						var timeout_id = setTimeout(function(){_run_delayed(id);}, delayed_operation.delay);
						delayed_operation.timeout_id = timeout_id;
					}
					else
					{
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
			}
			else
			{
				_repeat = false;
			}
			var _done;
			if (typeof done === 'function')
			{
				_done = done;
			}
			else
			{
				_done = null;
			}
			var delayed_operation =
			{
				operation : operation,
				id : id,
				delay : delay,
				done: _done,
				repeat : _repeat
			}
			delayed_operations.push(delayed_operation);
			var timeout_id = setTimeout(function(){_run_delayed(id);}, delay);
			delayed_operation.timeout_id = timeout_id;
			return id;
		};
		
		thoth.stop = function (id)
		{
			var count = delayed_operations.length;
			var delayed_operation;
			for (var index = 0; index < count; index++)
			{
				delayed_operation = delayed_operations[index];
				if (delayed_operation.id === id)
				{
					var timeout_id = delayed_operation.timeout_id;
					delayed_operations.splice(index, 1);
					_free_delayed_id(id);
					clearTimeout(timeout_id);
					return true;
				}
			}
			return false;
		};
		
		thoth.isRunning = function (id)
		{
			var count = delayed_operations.length;
			var delayed_operation;
			for (var index = 0; index < count; index++)
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
			
			this.contains = function (value) //Not used
			{
				for (var key in dic)
				{
					if (dic[key] === value)
					{
						return true;
					}
				}
				return false;
			}
			
			this.containsKey = function (key)
			{
				if (key in dic)
				{
					return true;
				}
				else
				{
					return false;
				}
			}
			
			this.containsWhere = function (predicate) //Not used
			{
				for (var key in dic)
				{
					if (predicate(dic[key]))
					{
						return true;
					}
				}
				return false;
			}
			
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
			}
			
			this.forEach = function(callback)
			{
				for (var key in dic)
				{
					callback(dic[key]);
				}
			}
			
			this.get = function (key)
			{
				if (key in dic)
				{
					return dic[key];
				}
				else
				{
					return undefined;
				}
			}
			
			this.length = function()
			{
				return length;
			}
			
			this.remove = function (key)
			{
				if (key in dic)
				{
					var result = dic[key];
					delete dic[key];
					length--;
					return result;
				}
				else
				{
					return undefined;
				}
			}
			
			this.removeWhere = function (predicate) //Not Used
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
			}
			
			this.set = function(key, item)
			{
				if (!(key in dic))
				{
					length++;
				}
				dic[key] = item;
			}
			
			this.toString = function()
			{
				var result = '';
				var first = true;
				for (var key in dic)
				{
					if (first)
					{
						first = false;
					}
					else
					{
						result += ',';
					}
					result += key;
				}
				return result;
			}
		}
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
					var step = function(event)
					{
						var continuations = event.continuations;
						if (continuations.length > 0)
						{
							var continuation = event.continuations.shift();
							continuation();
							thoth.delay(callin, 0, false);
						}
						else
						{
							event.executing = false;
							events.remove(event.id);
						}
					};
					thoth.delay(callin, 0, false);
				}
			}
			
			function _append_event(id, continuation)
			{
				var event = events.get(id);
				if (typeof event === 'undefined')
				{
					return false;
				}
				else
				{
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
							executing : false
						}
					);
				}
				return true;
			};
			
			this.containsKey = function(id)
			{
				return events.containsKey(id);
			}
			
			this.go = function (id)
			{
				var event = events.get(id);
				if (typeof event === 'undefined')
				{
					return false;
				}
				else
				{
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
				if (typeof event === 'undefined')
				{
					return false;
				}
				else
				{
					event.executing = false;
					events.remove(event.id);
					return true;
				}
			};
		};
		
		thoth.Dispatch.global = new thoth.Dispatch();
	}
)(window.thoth = (window.thoth || {}), window);

(	/* Events */
	function(thoth, window, undefined)
	{
		function addEventListener(element, eventName, handler)
		{
			if ("addEventListener" in element)
			{
				element.addEventListener(eventName, handler);
			}
			else
			{
				element.attachEvent (
					'on' + eventName,
					function()
					{
						handler.call(element);
					}
				);
			}
		}
		
		function removeEventListener(element, eventName, handler)
		{
			if ("removeEventListener" in element)
			{
				element.removeEventListener(eventName, handler);
			}
			else
			{
				element.detachEvent('on' + eventName, handler);
			}
		}
		
		thoth.on = function(element, events, handler)
		{
			if (typeof handler === 'function')
			{
				if (Array.isArray(events))
				{
					var index = 0;
					for (; index < events.length; index++)
					{
						addEventListener(element, events[index], handler);
					}
				}
				else
				{
					addEventListener(element, events, handler);
				}
			}
		}
		
		thoth.off = function(element, events, handler)
		{
			if (typeof handler === 'function')
			{
				if (Array.isArray(events))
				{
					var index = 0;
					for (; index < events.length; index++)
					{
						removeEventListener(element, events[index], handler);
					}
				}
				else
				{
					removeEventListener(element, events, handler);
				}
			}
		}
		
		thoth.ready = function(callback)
		{
			if (typeof callback === 'function')
			{
				if (document.readyState === 'loaded' || document.readyState === 'interactive' || document.readyState === 'complete')
				{
					callback();
				}
				else
				{
					if ("addEventListener" in document)
					{
						document.addEventListener('DOMContentLoaded', callback);
					}
					else
					{
						document.attachEvent (
							'onreadystatechange',
							function()
							{
								if (document.readyState === 'loaded' || document.readyState === 'interactive' || document.readyState === 'complete')
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
		}
	}
)(window.thoth = (window.thoth || {}), window);


(	/* include */
	function(thoth, window, undefined)
	{
		var SEPARATOR = "/";
		var _root_urls = [];
		var included_urls = [];
		var loading = new thoth.Dispatch();
		
		//--------------------------------------------------------------
		
		function _process_url(folders)
		{
			var result =  [];
			var folder = null;
			while (typeof (folder = folders.shift()) !== "undefined")
			{
				if (folder === ".")
				{
					continue;
				}
				if (folder === "..")
				{
					result.pop()
					continue;
				}
				result.push(folder);
			}
			return result;
		}
		
		function _process_absolute_url(absolute)
		{
			if (typeof absolute === "undefined")
			{
				return [];
			}
			else
			{
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
			if (typeof relative === "undefined")
			{
				return ["."];
			}
			else
			{
				if (relative.startsWith(SEPARATOR))
				{
					relative = relative.substr(SEPARATOR.length);
				}
				if (relative === "")
				{
					return ["."];
				}
				else
				{
					var folders = relative.split(SEPARATOR);
					return _process_url(folders);
				}
			}
		}
		
		function _resolve_url(url, callback)
		{
			var check = url.indexOf("/");
			if (check !== -1 && check === url.indexOf("//"))
			{
				//take as absolute or protocol relative
				callback(url);
			}
			else
			{
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
						}
						else
						{
							thoth.url_exists (
								_url,
								function (exists)
								{
									if (exists)
									{
										callback(_url);
										return true;
									}
									else
									{
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
			var head = document.getElementsByTagName("head")[0] || document.documentElement;
			var script = document.createElement('script');
			var done = false;
			if (typeof callback === 'function')
			{
				script.onload = script.onreadystatechange = function()
				{
					if (!done && (!this.readyState || this.readyState === "loaded" || this.readyState === "interactive" || this.readyState === "complete"))
					{
						done = true;
						var data = this.getAttribute("data-src");
						loading.go(data);
						loading.remove(data);
						this.onload = this.onreadystatechange = null;
						if (head && script.parentNode)
						{
							head.removeChild(script);
						}
					}
				}
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
								}
								else
								{
									callback();
								}
							}
							else
							{
								insert = true;
							}
						}
						else
						{
							insert = true;
							included_urls.push(resolved_url);
						}
						if (insert)
						{
							script.type = "text/javascript";
							script.async = true;
							script.src = resolved_url;
							script.setAttribute("data-src", resolved_url);
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
					}
					else
					{
						callback();
					}
				}
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
						}
						else
						{
							thoth.delay(go, 0, false);
						}
					}
					else
					{
						callback();
					}
				}
				thoth.delay(go, 0, false);
			}
		};
		
		thoth.configure = function(root_urls)
		{
			_root_urls = root_urls;
		}
		
		thoth.url_exists = function (url, callback)
		{
			var http = new XMLHttpRequest();
			var done = false;
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
		}
		
		thoth.resolve_relative_url = function (absolute, relative)
		{
			absolute = _process_absolute_url(absolute);
			relative = _process_relative_url(relative);
			var result = _process_url(absolute.concat(relative));
			return result.join(SEPARATOR);
		}
		
		//--------------------------------------------------------------
		
		window.include = thoth.include;
		window.include_once = thoth.include_once;
		
		var metaElements = window.document.getElementsByTagName('meta');
		for (var index = 0; index < metaElements.length; index++)
		{
			var meta = metaElements[index];
			if (meta.getAttribute('name') == 'thoth-load-script')
			{
				thoth.include_once(meta.getAttribute('content'));
			}
		}
	}
)(window.thoth = (window.thoth || {}), window);
