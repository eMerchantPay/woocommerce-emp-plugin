/**
 * Copyright (C) 2018-2025 emerchantpay Ltd.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author      emerchantpay Ltd.
 * @copyright   2018-2025 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 * @package     resources/js/frontend/EmpPopulateBrowserParams
 */

const empPopulateBrowserParams = {
	initParameters: function (methodName) {
		let java_enabled;
		try {
			java_enabled = navigator.javaEnabled();
		} catch (e) {
			java_enabled = false;
		}

		this.fieldNames = {
			[`${methodName}_java_enabled`]: java_enabled,
			[`${methodName}_color_depth`]: screen.colorDepth.toString(),
			[`${methodName}_browser_language`]: navigator.language,
			[`${methodName}_screen_height`]: screen.height.toString(),
			[`${methodName}_screen_width`]: screen.width.toString(),
			[`${methodName}_user_agent`]: navigator.userAgent,
			[`${methodName}_browser_timezone_zone_offset`]: (new Date()).getTimezoneOffset().toString()
		};
	},
	execute: function (methodName) {
		this.initParameters( methodName );
		return this.fieldNames;
	}
};

export default empPopulateBrowserParams;
