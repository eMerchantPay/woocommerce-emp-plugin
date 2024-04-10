/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./resources/js/frontend/CreditCardInputs.js":
/*!***************************************************!*\
  !*** ./resources/js/frontend/CreditCardInputs.js ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/html-entities */ "@wordpress/html-entities");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_1__);



const CreditCardInputs = ({
  handleInputChange,
  METHOD_NAME,
  directSettings,
  cardWrapperRef
}) => {
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "emp-direct-card-form"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_1__.decodeEntities)(directSettings.description || '')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    id: "emp-direct-card-wrapper",
    ref: cardWrapperRef
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, directSettings.show_cc_holder === 'yes' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    name: `${METHOD_NAME}-card-holder`,
    placeholder: "Cardholder Name",
    onChange: handleInputChange,
    autoComplete: "off",
    className: "emp-input-wide"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    name: `${METHOD_NAME}-card-number`,
    placeholder: "Card Number",
    onChange: handleInputChange,
    autoComplete: "off",
    className: "emp-input-wide"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "emp-input-half-wrapper"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    name: `${METHOD_NAME}-card-expiry`,
    placeholder: "Expiry Date",
    onChange: handleInputChange,
    autoComplete: "off",
    className: "emp-input-half"
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    name: `${METHOD_NAME}-card-cvc`,
    placeholder: "CVC",
    onChange: handleInputChange,
    autoComplete: "off",
    className: "emp-input-half"
  }))));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (CreditCardInputs);

/***/ }),

/***/ "./resources/js/frontend/EmerchantpayCheckout.js":
/*!*******************************************************!*\
  !*** ./resources/js/frontend/EmerchantpayCheckout.js ***!
  \*******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/html-entities */ "@wordpress/html-entities");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @woocommerce/settings */ "@woocommerce/settings");
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _ModalBlock__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./ModalBlock */ "./resources/js/frontend/ModalBlock.js");






const checkoutSettings = (0,_woocommerce_settings__WEBPACK_IMPORTED_MODULE_3__.getSetting)('emerchantpay-checkout-blocks_data', {});
const METHOD_NAME = 'emerchantpay_checkout';
let EmerchantpayBlocksCheckout = {};
if (Object.keys(checkoutSettings).length) {
  const defaultLabel = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Emerchantpay checkout', 'woocommerce-emerchantpay');
  const label = (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_2__.decodeEntities)(checkoutSettings.title) || defaultLabel;
  const Label = props => {
    const {
      PaymentMethodLabel
    } = props.components;
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(PaymentMethodLabel, {
      text: label
    });
  };
  const Description = props => {
    const {
      eventRegistration,
      emitResponse
    } = props;
    const {
      onPaymentProcessing,
      onCheckoutSuccess
    } = eventRegistration;
    (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
      if (checkoutSettings.iframe_processing !== 'yes') {
        return;
      }
      const handleCheckoutSuccess = props => {
        const parentDiv = document.querySelector('.emp-threeds-modal');
        const iframe = document.querySelector('.emp-threeds-iframe');
        const redirectUrl = props.processingResponse.paymentDetails.blocks_redirect;
        parentDiv.style.display = 'block';
        iframe.style.display = 'block';
        iframe.src = redirectUrl;
      };
      const unsubscribe = onCheckoutSuccess(handleCheckoutSuccess);
      return () => {
        unsubscribe();
      };
    }, [onCheckoutSuccess, checkoutSettings.iframe_processing]);
    (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
      const unsubscribe = onPaymentProcessing(async () => {
        const paymentMethodData = {
          [`${METHOD_NAME}_blocks_order`]: true
        };
        return {
          type: emitResponse.responseTypes.SUCCESS,
          meta: {
            paymentMethodData
          }
        };
      });
      return () => {
        unsubscribe();
      };
    }, [emitResponse.responseTypes.ERROR, emitResponse.responseTypes.SUCCESS, onPaymentProcessing]);
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_2__.decodeEntities)(checkoutSettings.description || '')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_ModalBlock__WEBPACK_IMPORTED_MODULE_4__["default"], null));
  };
  EmerchantpayBlocksCheckout = {
    name: "emerchantpay_checkout",
    label: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Label, null),
    content: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Description, null),
    edit: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Description, null),
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
      features: checkoutSettings.supports
    }
  };
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (EmerchantpayBlocksCheckout);

/***/ }),

/***/ "./resources/js/frontend/EmerchantpayDirect.js":
/*!*****************************************************!*\
  !*** ./resources/js/frontend/EmerchantpayDirect.js ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/html-entities */ "@wordpress/html-entities");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @woocommerce/settings */ "@woocommerce/settings");
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _CreditCardInputs__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./CreditCardInputs */ "./resources/js/frontend/CreditCardInputs.js");
/* harmony import */ var _ModalBlock__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./ModalBlock */ "./resources/js/frontend/ModalBlock.js");
/* harmony import */ var _EmpPopulateBrowserParams__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./EmpPopulateBrowserParams */ "./resources/js/frontend/EmpPopulateBrowserParams.js");








const directSettings = (0,_woocommerce_settings__WEBPACK_IMPORTED_MODULE_3__.getSetting)('emerchantpay-direct-blocks_data', {});
const METHOD_NAME = 'emerchantpay_direct';
const CreditCardForm = props => {
  const [creditCardData, setCreditCardData] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)({});
  const cardWrapperRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  const browserParams = _EmpPopulateBrowserParams__WEBPACK_IMPORTED_MODULE_6__["default"].execute(METHOD_NAME);
  const {
    eventRegistration,
    emitResponse
  } = props;
  const {
    onPaymentProcessing,
    onCheckoutSuccess
  } = eventRegistration;
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    const unsubscribe = onPaymentProcessing(async () => {
      const blocksCheckout = {
        [`${METHOD_NAME}_blocks_order`]: true
      };
      const paymentMethodData = {
        ...browserParams,
        ...creditCardData,
        ...blocksCheckout
      };
      return {
        type: emitResponse.responseTypes.SUCCESS,
        meta: {
          paymentMethodData
        }
      };
    });
    return () => {
      unsubscribe();
    };
  }, [emitResponse.responseTypes.ERROR, emitResponse.responseTypes.SUCCESS, onPaymentProcessing, creditCardData]);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (directSettings.iframe_processing !== 'yes') {
      return;
    }
    const handleCheckoutSuccess = props => {
      const iframe = document.querySelector('.emp-threeds-iframe');
      const parentDiv = document.querySelector('.emp-threeds-modal');
      const redirectUrl = props.processingResponse.paymentDetails.blocks_redirect;
      iframe.style.display = 'block';
      try {
        fetch(redirectUrl, {
          method: 'GET'
        }).then(function (response) {
          return response.text();
        }).then(function (html) {
          const doc = iframe.contentWindow.document;
          doc.open();
          doc.write(html);
          doc.close();
          parentDiv.style.display = 'block';
        });
      } catch (e) {}
    };
    const unsubscribe = onCheckoutSuccess(handleCheckoutSuccess);
    return () => {
      unsubscribe();
    };
  }, [onCheckoutSuccess, directSettings.iframe_processing]);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    __webpack_require__.e(/*! import() */ "resources_js_frontend_card_js").then(__webpack_require__.t.bind(__webpack_require__, /*! ./card.js */ "./resources/js/frontend/card.js", 23)).then(Card => {
      new Card.default({
        form: '.wc-block-checkout__form',
        container: cardWrapperRef.current,
        formSelectors: {
          numberInput: `input[name="${METHOD_NAME}-card-number"]`,
          expiryInput: `input[name="${METHOD_NAME}-card-expiry"]`,
          cvcInput: `input[name="${METHOD_NAME}-card-cvc"]`,
          nameInput: `input[name="${METHOD_NAME}-card-holder"]`
        }
      });
    }).catch(error => console.error('Error loading card.js:', error));
  }, []);
  const handleInputChange = e => {
    setCreditCardData(prevData => ({
      ...prevData,
      [e.target.name]: e.target.value
    }));
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_CreditCardInputs__WEBPACK_IMPORTED_MODULE_4__["default"], {
    handleInputChange: handleInputChange,
    METHOD_NAME: METHOD_NAME,
    directSettings: directSettings,
    cardWrapperRef: cardWrapperRef
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_ModalBlock__WEBPACK_IMPORTED_MODULE_5__["default"], null));
};
let EmerchantpayBlocksDirect = {};
if (Object.keys(directSettings).length) {
  const defaultLabel = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Emerchantpay direct', 'woocommerce-emerchantpay');
  const label = (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_2__.decodeEntities)(directSettings.title) || defaultLabel;
  EmerchantpayBlocksDirect = {
    name: METHOD_NAME,
    label: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, label),
    content: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(CreditCardForm, null),
    edit: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(CreditCardForm, null),
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
      features: directSettings.supports
    }
  };
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (EmerchantpayBlocksDirect);

/***/ }),

/***/ "./resources/js/frontend/EmpPopulateBrowserParams.js":
/*!***********************************************************!*\
  !*** ./resources/js/frontend/EmpPopulateBrowserParams.js ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
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
      [`${methodName}_browser_timezone_zone_offset`]: new Date().getTimezoneOffset().toString()
    };
  },
  execute: function (methodName) {
    this.initParameters(methodName);
    return this.fieldNames;
  }
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (empPopulateBrowserParams);

/***/ }),

/***/ "./resources/js/frontend/ModalBlock.js":
/*!*********************************************!*\
  !*** ./resources/js/frontend/ModalBlock.js ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);


const ModalBlock = () => {
  const iframeRef = (0,react__WEBPACK_IMPORTED_MODULE_0__.useRef)(null);
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    const iframe = iframeRef.current;
    const document = iframe.contentDocument || iframe.contentWindow.document;
    const content = `
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
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "emp-threeds-modal"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("iframe", {
    ref: iframeRef,
    className: "emp-threeds-iframe",
    frameBorder: "0",
    style: {
      border: 'none',
      'border-radius': '10px',
      display: 'none'
    }
  }));
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (ModalBlock);

/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

module.exports = window["React"];

/***/ }),

/***/ "@woocommerce/blocks-registry":
/*!******************************************!*\
  !*** external ["wc","wcBlocksRegistry"] ***!
  \******************************************/
/***/ ((module) => {

module.exports = window["wc"]["wcBlocksRegistry"];

/***/ }),

/***/ "@woocommerce/settings":
/*!************************************!*\
  !*** external ["wc","wcSettings"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wc"]["wcSettings"];

/***/ }),

/***/ "@wordpress/html-entities":
/*!**************************************!*\
  !*** external ["wp","htmlEntities"] ***!
  \**************************************/
/***/ ((module) => {

module.exports = window["wp"]["htmlEntities"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/create fake namespace object */
/******/ 	(() => {
/******/ 		var getProto = Object.getPrototypeOf ? (obj) => (Object.getPrototypeOf(obj)) : (obj) => (obj.__proto__);
/******/ 		var leafPrototypes;
/******/ 		// create a fake namespace object
/******/ 		// mode & 1: value is a module id, require it
/******/ 		// mode & 2: merge all properties of value into the ns
/******/ 		// mode & 4: return value when already ns object
/******/ 		// mode & 16: return value when it's Promise-like
/******/ 		// mode & 8|1: behave like require
/******/ 		__webpack_require__.t = function(value, mode) {
/******/ 			if(mode & 1) value = this(value);
/******/ 			if(mode & 8) return value;
/******/ 			if(typeof value === 'object' && value) {
/******/ 				if((mode & 4) && value.__esModule) return value;
/******/ 				if((mode & 16) && typeof value.then === 'function') return value;
/******/ 			}
/******/ 			var ns = Object.create(null);
/******/ 			__webpack_require__.r(ns);
/******/ 			var def = {};
/******/ 			leafPrototypes = leafPrototypes || [null, getProto({}), getProto([]), getProto(getProto)];
/******/ 			for(var current = mode & 2 && value; typeof current == 'object' && !~leafPrototypes.indexOf(current); current = getProto(current)) {
/******/ 				Object.getOwnPropertyNames(current).forEach((key) => (def[key] = () => (value[key])));
/******/ 			}
/******/ 			def['default'] = () => (value);
/******/ 			__webpack_require__.d(ns, def);
/******/ 			return ns;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/ensure chunk */
/******/ 	(() => {
/******/ 		__webpack_require__.f = {};
/******/ 		// This file contains only the entry chunk.
/******/ 		// The chunk loading function for additional chunks
/******/ 		__webpack_require__.e = (chunkId) => {
/******/ 			return Promise.all(Object.keys(__webpack_require__.f).reduce((promises, key) => {
/******/ 				__webpack_require__.f[key](chunkId, promises);
/******/ 				return promises;
/******/ 			}, []));
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/get javascript chunk filename */
/******/ 	(() => {
/******/ 		// This function allow to reference async chunks
/******/ 		__webpack_require__.u = (chunkId) => {
/******/ 			// return url for filenames based on template
/******/ 			return "" + chunkId + ".js";
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/get mini-css chunk filename */
/******/ 	(() => {
/******/ 		// This function allow to reference async chunks
/******/ 		__webpack_require__.miniCssF = (chunkId) => {
/******/ 			// return url for filenames based on template
/******/ 			return undefined;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/global */
/******/ 	(() => {
/******/ 		__webpack_require__.g = (function() {
/******/ 			if (typeof globalThis === 'object') return globalThis;
/******/ 			try {
/******/ 				return this || new Function('return this')();
/******/ 			} catch (e) {
/******/ 				if (typeof window === 'object') return window;
/******/ 			}
/******/ 		})();
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/load script */
/******/ 	(() => {
/******/ 		var inProgress = {};
/******/ 		var dataWebpackPrefix = "emerchantpay-payment-page-for-woocommerce:";
/******/ 		// loadScript function to load a script via script tag
/******/ 		__webpack_require__.l = (url, done, key, chunkId) => {
/******/ 			if(inProgress[url]) { inProgress[url].push(done); return; }
/******/ 			var script, needAttach;
/******/ 			if(key !== undefined) {
/******/ 				var scripts = document.getElementsByTagName("script");
/******/ 				for(var i = 0; i < scripts.length; i++) {
/******/ 					var s = scripts[i];
/******/ 					if(s.getAttribute("src") == url || s.getAttribute("data-webpack") == dataWebpackPrefix + key) { script = s; break; }
/******/ 				}
/******/ 			}
/******/ 			if(!script) {
/******/ 				needAttach = true;
/******/ 				script = document.createElement('script');
/******/ 		
/******/ 				script.charset = 'utf-8';
/******/ 				script.timeout = 120;
/******/ 				if (__webpack_require__.nc) {
/******/ 					script.setAttribute("nonce", __webpack_require__.nc);
/******/ 				}
/******/ 				script.setAttribute("data-webpack", dataWebpackPrefix + key);
/******/ 		
/******/ 				script.src = url;
/******/ 			}
/******/ 			inProgress[url] = [done];
/******/ 			var onScriptComplete = (prev, event) => {
/******/ 				// avoid mem leaks in IE.
/******/ 				script.onerror = script.onload = null;
/******/ 				clearTimeout(timeout);
/******/ 				var doneFns = inProgress[url];
/******/ 				delete inProgress[url];
/******/ 				script.parentNode && script.parentNode.removeChild(script);
/******/ 				doneFns && doneFns.forEach((fn) => (fn(event)));
/******/ 				if(prev) return prev(event);
/******/ 			}
/******/ 			var timeout = setTimeout(onScriptComplete.bind(null, undefined, { type: 'timeout', target: script }), 120000);
/******/ 			script.onerror = onScriptComplete.bind(null, script.onerror);
/******/ 			script.onload = onScriptComplete.bind(null, script.onload);
/******/ 			needAttach && document.head.appendChild(script);
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/publicPath */
/******/ 	(() => {
/******/ 		var scriptUrl;
/******/ 		if (__webpack_require__.g.importScripts) scriptUrl = __webpack_require__.g.location + "";
/******/ 		var document = __webpack_require__.g.document;
/******/ 		if (!scriptUrl && document) {
/******/ 			if (document.currentScript)
/******/ 				scriptUrl = document.currentScript.src;
/******/ 			if (!scriptUrl) {
/******/ 				var scripts = document.getElementsByTagName("script");
/******/ 				if(scripts.length) {
/******/ 					var i = scripts.length - 1;
/******/ 					while (i > -1 && (!scriptUrl || !/^http(s?):/.test(scriptUrl))) scriptUrl = scripts[i--].src;
/******/ 				}
/******/ 			}
/******/ 		}
/******/ 		// When supporting browsers where an automatic publicPath is not supported you must specify an output.publicPath manually via configuration
/******/ 		// or pass an empty string ("") and set the __webpack_public_path__ variable from your code to use your own logic.
/******/ 		if (!scriptUrl) throw new Error("Automatic publicPath is not supported in this browser");
/******/ 		scriptUrl = scriptUrl.replace(/#.*$/, "").replace(/\?.*$/, "").replace(/\/[^\/]+$/, "/");
/******/ 		__webpack_require__.p = scriptUrl + "../";
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		// no baseURI
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"frontend/blocks": 0
/******/ 		};
/******/ 		
/******/ 		__webpack_require__.f.j = (chunkId, promises) => {
/******/ 				// JSONP chunk loading for javascript
/******/ 				var installedChunkData = __webpack_require__.o(installedChunks, chunkId) ? installedChunks[chunkId] : undefined;
/******/ 				if(installedChunkData !== 0) { // 0 means "already installed".
/******/ 		
/******/ 					// a Promise means "currently loading".
/******/ 					if(installedChunkData) {
/******/ 						promises.push(installedChunkData[2]);
/******/ 					} else {
/******/ 						if(true) { // all chunks have JS
/******/ 							// setup Promise in chunk cache
/******/ 							var promise = new Promise((resolve, reject) => (installedChunkData = installedChunks[chunkId] = [resolve, reject]));
/******/ 							promises.push(installedChunkData[2] = promise);
/******/ 		
/******/ 							// start chunk loading
/******/ 							var url = __webpack_require__.p + __webpack_require__.u(chunkId);
/******/ 							// create error before stack unwound to get useful stacktrace later
/******/ 							var error = new Error();
/******/ 							var loadingEnded = (event) => {
/******/ 								if(__webpack_require__.o(installedChunks, chunkId)) {
/******/ 									installedChunkData = installedChunks[chunkId];
/******/ 									if(installedChunkData !== 0) installedChunks[chunkId] = undefined;
/******/ 									if(installedChunkData) {
/******/ 										var errorType = event && (event.type === 'load' ? 'missing' : event.type);
/******/ 										var realSrc = event && event.target && event.target.src;
/******/ 										error.message = 'Loading chunk ' + chunkId + ' failed.\n(' + errorType + ': ' + realSrc + ')';
/******/ 										error.name = 'ChunkLoadError';
/******/ 										error.type = errorType;
/******/ 										error.request = realSrc;
/******/ 										installedChunkData[1](error);
/******/ 									}
/******/ 								}
/******/ 							};
/******/ 							__webpack_require__.l(url, loadingEnded, "chunk-" + chunkId, chunkId);
/******/ 						}
/******/ 					}
/******/ 				}
/******/ 		};
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		// no on chunks loaded
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var [chunkIds, moreModules, runtime] = data;
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some((id) => (installedChunks[id] !== 0))) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 		
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = globalThis["webpackChunkemerchantpay_payment_page_for_woocommerce"] = globalThis["webpackChunkemerchantpay_payment_page_for_woocommerce"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
(() => {
/*!****************************************!*\
  !*** ./resources/js/frontend/index.js ***!
  \****************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @woocommerce/blocks-registry */ "@woocommerce/blocks-registry");
/* harmony import */ var _woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _EmerchantpayCheckout__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./EmerchantpayCheckout */ "./resources/js/frontend/EmerchantpayCheckout.js");
/* harmony import */ var _EmerchantpayDirect__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./EmerchantpayDirect */ "./resources/js/frontend/EmerchantpayDirect.js");



if (Object.keys(_EmerchantpayCheckout__WEBPACK_IMPORTED_MODULE_1__["default"]).length > 0) {
  (0,_woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_0__.registerPaymentMethod)(_EmerchantpayCheckout__WEBPACK_IMPORTED_MODULE_1__["default"]);
}
if (Object.keys(_EmerchantpayDirect__WEBPACK_IMPORTED_MODULE_2__["default"]).length > 0) {
  (0,_woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_0__.registerPaymentMethod)(_EmerchantpayDirect__WEBPACK_IMPORTED_MODULE_2__["default"]);
}
})();

/******/ })()
;
//# sourceMappingURL=blocks.js.map