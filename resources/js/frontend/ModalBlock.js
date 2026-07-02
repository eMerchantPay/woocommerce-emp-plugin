/**
 * Copyright (C) 2018-2024 emerchantpay Ltd.
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
 * @copyright   2018-2024 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 * @package     resources/js/frontend/ModalBlock
 */

import React, { useEffect, useRef } from 'react';

const ModalBlock = () => {
	const iframeRef = useRef(null);

	useEffect(() => {
		const iframe   = iframeRef.current;
		const document = iframe.contentDocument || iframe.contentWindow.document;
		const content  = `
	  <html>
	  <head>
		<title>Payment Processing</title>
		<style>
		  body { font-family: Arial, sans-serif; text-align: center; background-color: #fff; overflow: hidden; }
		  .center { display: flex; justify-content: center; align-items: center; height: 100vh; }
		  .content { text-align: center; }
		  .screen-logo img { width: 100px; }
		  h3 { color: #333; }
		  h3 span { display: block; margin-top: 20px; font-size: 0.9em; }
		</style>
	  </head>
	  <body>
		<div class="center">
		  <div class="content">
			<h3>The payment is being processed<span>Please wait</span></h3>
		  </div>
		</div>
	  </body>
	  </html>
	`;
		document.open();
		document.write(content);
		document.close();
	}, []);

	return (
		<div className="emp-threeds-modal">
		<iframe ref={iframeRef} className="emp-threeds-iframe" frameBorder="0" style={{border: 'none', 'border-radius': '10px', display: 'none'}}></iframe>
	</div>
);
};

export default ModalBlock;
