(self.webpackChunkhire_tracker_app=self.webpackChunkhire_tracker_app||[]).push([[983],{2983:function($,Z,Q){var Y;typeof globalThis<"u"?globalThis:typeof this<"u"||(typeof window<"u"?window:typeof self<"u"?self:global),$.exports=(Y=function(){try{return Q(3583)}catch{}}(),function(){"use strict";var J={662:function(A,m){var v,W=this&&this.__extends||(v=function(s,c){return(v=Object.setPrototypeOf||{__proto__:[]}instanceof Array&&function(w,d){w.__proto__=d}||function(w,d){for(var u in d)Object.prototype.hasOwnProperty.call(d,u)&&(w[u]=d[u])})(s,c)},function(s,c){if("function"!=typeof c&&null!==c)throw new TypeError("Class extends value "+String(c)+" is not a constructor or null");function w(){this.constructor=s}v(s,c),s.prototype=null===c?Object.create(c):(w.prototype=c.prototype,new w)});Object.defineProperty(m,"__esModule",{value:!0}),m.CellHookData=m.HookData=void 0;var x=function v(s,c,w){this.table=c,this.pageNumber=c.pageNumber,this.pageCount=this.pageNumber,this.settings=c.settings,this.cursor=w,this.doc=s.getDocument()};m.HookData=x;var H=function(v){function s(c,w,d,u,n,t){var e=v.call(this,c,w,t)||this;return e.cell=d,e.row=u,e.column=n,e.section=u.section,e}return W(s,v),s}(x);m.CellHookData=H},790:function(A,m,W){Object.defineProperty(m,"__esModule",{value:!0});var x=W(148),H=W(938),v=W(323),s=W(587),c=W(49),w=W(858);m.default=function d(u){u.API.autoTable=function(){for(var n=[],t=0;t<arguments.length;t++)n[t]=arguments[t];var e;1===n.length?e=n[0]:(console.error("Use of deprecated autoTable initiation"),(e=n[2]||{}).columns=n[0],e.body=n[1]);var r=(0,s.parseInput)(this,e),o=(0,w.createTable)(this,r);return(0,c.drawTable)(this,o),this},u.API.lastAutoTable=!1,u.API.previousAutoTable=!1,u.API.autoTable.previous=!1,u.API.autoTableText=function(n,t,e,r){(0,H.default)(n,t,e,r,this)},u.API.autoTableSetDefaults=function(n){return v.DocHandler.setDefaults(n,this),this},u.autoTableSetDefaults=function(n,t){v.DocHandler.setDefaults(n,t)},u.API.autoTableHtmlToJson=function(n,t){if(void 0===t&&(t=!1),typeof window>"u")return console.error("Cannot run autoTableHtmlToJson in non browser environment"),null;var e=new v.DocHandler(this),r=(0,x.parseHtml)(e,n,window,t,!1),i=r.body;return{columns:r.head[0].map(function(h){return h.content}),rows:i,data:i}},u.API.autoTableEndPosY=function(){console.error("Use of deprecated function: autoTableEndPosY. Use doc.lastAutoTable.finalY instead.");var n=this.lastAutoTable;return n&&n.finalY?n.finalY:0},u.API.autoTableAddPageContent=function(n){return console.error("Use of deprecated function: autoTableAddPageContent. Use jsPDF.autoTableSetDefaults({didDrawPage: () => {}}) instead."),u.API.autoTable.globalDefaults||(u.API.autoTable.globalDefaults={}),u.API.autoTable.globalDefaults.addPageContent=n,this},u.API.autoTableAddPage=function(){return console.error("Use of deprecated function: autoTableAddPage. Use doc.addPage()"),this.addPage(),this}}},938:function(A,m){Object.defineProperty(m,"__esModule",{value:!0}),m.default=function W(x,H,v,s,c){s=s||{};var w=1.15,d=c.internal.scaleFactor,u=c.internal.getFontSize()/d,t="",e=1;if(("middle"===s.valign||"bottom"===s.valign||"center"===s.halign||"right"===s.halign)&&(e=(t="string"==typeof x?x.split(/\r\n|\r|\n/g):x).length||1),v+=u*(2-w),"middle"===s.valign?v-=e/2*u*w:"bottom"===s.valign&&(v-=e*u*w),"center"===s.halign||"right"===s.halign){var r=u;if("center"===s.halign&&(r*=.5),t&&e>=1){for(var o=0;o<t.length;o++)c.text(t[o],H-c.getStringUnitWidth(t[o])*r,v),v+=u*w;return c}H-=c.getStringUnitWidth(x)*r}return"justify"===s.halign?c.text(x,H,v,{maxWidth:s.maxWidth||100,align:"justify"}):c.text(x,H,v),c}},200:function(A,m){function H(s,c){var w=s>0,d=c||0===c;return w&&d?"DF":w?"S":d?"F":null}Object.defineProperty(m,"__esModule",{value:!0}),m.parseSpacing=m.getFillStyle=m.addTableBorder=m.getStringWidth=void 0,m.getStringWidth=function W(s,c,w){return w.applyStyles(c,!0),(Array.isArray(s)?s:[s]).map(function(n){return w.getTextWidth(n)}).reduce(function(n,t){return Math.max(n,t)},0)},m.addTableBorder=function x(s,c,w,d){var u=c.settings.tableLineWidth;s.applyStyles({lineWidth:u,lineColor:c.settings.tableLineColor});var t=H(u,!1);t&&s.rect(w.x,w.y,c.getWidth(s.pageSize().width),d.y-w.y,t)},m.getFillStyle=H,m.parseSpacing=function v(s,c){var w,d,u,n;if(s=s||c,Array.isArray(s)){if(s.length>=4)return{top:s[0],right:s[1],bottom:s[2],left:s[3]};if(3===s.length)return{top:s[0],right:s[1],bottom:s[2],left:s[1]};if(2===s.length)return{top:s[0],right:s[1],bottom:s[0],left:s[1]};s=1===s.length?s[0]:c}return"object"==typeof s?("number"==typeof s.vertical&&(s.top=s.vertical,s.bottom=s.vertical),"number"==typeof s.horizontal&&(s.right=s.horizontal,s.left=s.horizontal),{left:null!==(w=s.left)&&void 0!==w?w:c,top:null!==(d=s.top)&&void 0!==d?d:c,right:null!==(u=s.right)&&void 0!==u?u:c,bottom:null!==(n=s.bottom)&&void 0!==n?n:c}):("number"!=typeof s&&(s=c),{top:s,right:s,bottom:s,left:s})}},913:function(A,m){var s,W=this&&this.__extends||(s=function(c,w){return(s=Object.setPrototypeOf||{__proto__:[]}instanceof Array&&function(d,u){d.__proto__=u}||function(d,u){for(var n in u)Object.prototype.hasOwnProperty.call(u,n)&&(d[n]=u[n])})(c,w)},function(c,w){if("function"!=typeof w&&null!==w)throw new TypeError("Class extends value "+String(w)+" is not a constructor or null");function d(){this.constructor=c}s(c,w),c.prototype=null===w?Object.create(w):(d.prototype=w.prototype,new d)});Object.defineProperty(m,"__esModule",{value:!0}),m.getTheme=m.defaultStyles=m.HtmlRowInput=m.FONT_ROW_RATIO=void 0,m.FONT_ROW_RATIO=1.15;var x=function(s){function c(w){var d=s.call(this)||this;return d._element=w,d}return W(c,s),c}(Array);m.HtmlRowInput=x,m.defaultStyles=function H(s){return{font:"helvetica",fontStyle:"normal",overflow:"linebreak",fillColor:!1,textColor:20,halign:"left",valign:"top",fontSize:10,cellPadding:5/s,lineColor:200,lineWidth:0,cellWidth:"auto",minCellHeight:0,minCellWidth:0}},m.getTheme=function v(s){return{striped:{table:{fillColor:255,textColor:80,fontStyle:"normal"},head:{textColor:255,fillColor:[41,128,185],fontStyle:"bold"},body:{},foot:{textColor:255,fillColor:[41,128,185],fontStyle:"bold"},alternateRow:{fillColor:245}},grid:{table:{fillColor:255,textColor:80,fontStyle:"normal",lineWidth:.1},head:{textColor:255,fillColor:[26,188,156],fontStyle:"bold",lineWidth:0},body:{},foot:{textColor:255,fillColor:[26,188,156],fontStyle:"bold",lineWidth:0},alternateRow:{}},plain:{head:{fontStyle:"bold"},foot:{fontStyle:"bold"}}}[s]}},259:function(A,m,W){Object.defineProperty(m,"__esModule",{value:!0}),m.parseCss=void 0;var x=W(200);function s(d,u){var n=c(d,u);if(!n)return null;var t=n.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d*\.?\d*))?\)$/);if(!t||!Array.isArray(t))return null;var e=[parseInt(t[1]),parseInt(t[2]),parseInt(t[3])];return 0===parseInt(t[4])||isNaN(e[0])||isNaN(e[1])||isNaN(e[2])?null:e}function c(d,u){var n=u(d);return"rgba(0, 0, 0, 0)"===n||"transparent"===n||"initial"===n||"inherit"===n?null==d.parentElement?null:c(d.parentElement,u):n}m.parseCss=function H(d,u,n,t,e){var r={},o=1.3333333333333333,i=s(u,function(O){return e.getComputedStyle(O).backgroundColor});null!=i&&(r.fillColor=i);var f=s(u,function(O){return e.getComputedStyle(O).color});null!=f&&(r.textColor=f);var h=s(u,function(O){return e.getComputedStyle(O).borderTopColor});null!=h&&(r.lineColor=h);var g=function w(d,u){var n=[d.paddingTop,d.paddingRight,d.paddingBottom,d.paddingLeft],t=96/(72/u),e=(parseInt(d.lineHeight)-parseInt(d.fontSize))/u/2,r=n.map(function(i){return parseInt(i||"0")/t}),o=(0,x.parseSpacing)(r,0);return e>o.top&&(o.top=e),e>o.bottom&&(o.bottom=e),o}(t,n);g&&(r.cellPadding=g);var y=parseInt(t.borderTopWidth||"");(y=y/o/n)&&(r.lineWidth=y);var S=["left","right","center","justify"];-1!==S.indexOf(t.textAlign)&&(r.halign=t.textAlign),-1!==(S=["middle","bottom","top"]).indexOf(t.verticalAlign)&&(r.valign=t.verticalAlign);var a=parseInt(t.fontSize||"");isNaN(a)||(r.fontSize=a/o);var D=function v(d){var u="";return("bold"===d.fontWeight||"bolder"===d.fontWeight||parseInt(d.fontWeight)>=700)&&(u="bold"),("italic"===d.fontStyle||"oblique"===d.fontStyle)&&(u+="italic"),u}(t);D&&(r.fontStyle=D);var R=(t.fontFamily||"").toLowerCase();return-1!==d.indexOf(R)&&(r.font=R),r}},323:function(A,m){Object.defineProperty(m,"__esModule",{value:!0}),m.DocHandler=void 0;var W={},x=function(){function H(v){this.jsPDFDocument=v,this.userStyles={textColor:v.getTextColor?this.jsPDFDocument.getTextColor():0,fontSize:v.internal.getFontSize(),fontStyle:v.internal.getFont().fontStyle,font:v.internal.getFont().fontName,lineWidth:v.getLineWidth?this.jsPDFDocument.getLineWidth():0,lineColor:v.getDrawColor?this.jsPDFDocument.getDrawColor():0}}return H.setDefaults=function(v,s){void 0===s&&(s=null),s?s.__autoTableDocumentDefaults=v:W=v},H.unifyColor=function(v){return Array.isArray(v)?v:"number"==typeof v?[v,v,v]:"string"==typeof v?[v]:null},H.prototype.applyStyles=function(v,s){var c,w,d;void 0===s&&(s=!1),v.fontStyle&&this.jsPDFDocument.setFontStyle&&this.jsPDFDocument.setFontStyle(v.fontStyle);var u=this.jsPDFDocument.internal.getFont(),n=u.fontStyle,t=u.fontName;if(v.font&&(t=v.font),v.fontStyle){n=v.fontStyle;var e=this.getFontList()[t];e&&-1===e.indexOf(n)&&(this.jsPDFDocument.setFontStyle&&this.jsPDFDocument.setFontStyle(e[0]),n=e[0])}if(this.jsPDFDocument.setFont(t,n),v.fontSize&&this.jsPDFDocument.setFontSize(v.fontSize),!s){var r=H.unifyColor(v.fillColor);r&&(c=this.jsPDFDocument).setFillColor.apply(c,r),(r=H.unifyColor(v.textColor))&&(w=this.jsPDFDocument).setTextColor.apply(w,r),(r=H.unifyColor(v.lineColor))&&(d=this.jsPDFDocument).setDrawColor.apply(d,r),"number"==typeof v.lineWidth&&this.jsPDFDocument.setLineWidth(v.lineWidth)}},H.prototype.splitTextToSize=function(v,s,c){return this.jsPDFDocument.splitTextToSize(v,s,c)},H.prototype.rect=function(v,s,c,w,d){return this.jsPDFDocument.rect(v,s,c,w,d)},H.prototype.getLastAutoTable=function(){return this.jsPDFDocument.lastAutoTable||null},H.prototype.getTextWidth=function(v){return this.jsPDFDocument.getTextWidth(v)},H.prototype.getDocument=function(){return this.jsPDFDocument},H.prototype.setPage=function(v){this.jsPDFDocument.setPage(v)},H.prototype.addPage=function(){return this.jsPDFDocument.addPage()},H.prototype.getFontList=function(){return this.jsPDFDocument.getFontList()},H.prototype.getGlobalOptions=function(){return W||{}},H.prototype.getDocumentOptions=function(){return this.jsPDFDocument.__autoTableDocumentDefaults||{}},H.prototype.pageSize=function(){var v=this.jsPDFDocument.internal.pageSize;return null==v.width&&(v={width:v.getWidth(),height:v.getHeight()}),v},H.prototype.scaleFactor=function(){return this.jsPDFDocument.internal.scaleFactor},H.prototype.pageNumber=function(){var v=this.jsPDFDocument.internal.getCurrentPageInfo();return v?v.pageNumber:this.jsPDFDocument.internal.getNumberOfPages()},H}();m.DocHandler=x},148:function(A,m,W){Object.defineProperty(m,"__esModule",{value:!0}),m.parseHtml=void 0;var x=W(259),H=W(913);function s(w,d,u,n,t,e){for(var r=new H.HtmlRowInput(n),o=0;o<n.cells.length;o++){var i=n.cells[o],f=u.getComputedStyle(i);if(t||"none"!==f.display){var h=void 0;e&&(h=(0,x.parseCss)(w,i,d,f,u)),r.push({rowSpan:i.rowSpan,colSpan:i.colSpan,styles:h,_element:i,content:c(i)})}}var g=u.getComputedStyle(n);if(r.length>0&&(t||"none"!==g.display))return r}function c(w){var d=w.cloneNode(!0);return d.innerHTML=d.innerHTML.replace(/\n/g,"").replace(/ +/g," "),d.innerHTML=d.innerHTML.split(/\<br.*?\>/).map(function(u){return u.trim()}).join("\n"),d.innerText||d.textContent||""}m.parseHtml=function v(w,d,u,n,t){var e,r,o;void 0===n&&(n=!1),void 0===t&&(t=!1),o="string"==typeof d?u.document.querySelector(d):d;var i=Object.keys(w.getFontList()),f=w.scaleFactor(),h=[],g=[],y=[];if(!o)return console.error("Html table could not be found with input: ",d),{head:h,body:g,foot:y};for(var S=0;S<o.rows.length;S++){var a=o.rows[S],D=null===(r=null===(e=a?.parentElement)||void 0===e?void 0:e.tagName)||void 0===r?void 0:r.toLowerCase(),R=s(i,f,u,a,n,t);!R||("thead"===D?h.push(R):"tfoot"===D?y.push(R):g.push(R))}return{head:h,body:g,foot:y}}},587:function(A,m,W){Object.defineProperty(m,"__esModule",{value:!0}),m.parseInput=void 0;var x=W(148),H=W(360),v=W(200),s=W(323),c=W(291);function r(o,i,f){var h=o[0]||i[0]||f[0]||[],g=[];return Object.keys(h).filter(function(y){return"_element"!==y}).forEach(function(y){var a,S=1;"object"==typeof(a=Array.isArray(h)?h[parseInt(y)]:h[y])&&!Array.isArray(a)&&(S=a?.colSpan||1);for(var D=0;D<S;D++){var R;R=Array.isArray(h)?g.length:y+(D>0?"_".concat(D):""),g.push({dataKey:R})}}),g}m.parseInput=function w(o,i){var f=new s.DocHandler(o),h=f.getDocumentOptions(),g=f.getGlobalOptions();(0,c.default)(f,g,h,i);var S,y=(0,H.assign)({},g,h,i);typeof window<"u"&&(S=window);var a=function d(o,i,f){for(var h={styles:{},headStyles:{},bodyStyles:{},footStyles:{},alternateRowStyles:{},columnStyles:{}},g=function(D){if("columnStyles"===D)h.columnStyles=(0,H.assign)({},o[D],i[D],f[D]);else{var l=[o,i,f].map(function(P){return P[D]||{}});h[D]=(0,H.assign)({},l[0],l[1],l[2])}},y=0,S=Object.keys(h);y<S.length;y++)g(S[y]);return h}(g,h,i),D=function u(o,i,f){for(var g={didParseCell:[],willDrawCell:[],didDrawCell:[],didDrawPage:[]},y=0,S=[o,i,f];y<S.length;y++){var a=S[y];a.didParseCell&&g.didParseCell.push(a.didParseCell),a.willDrawCell&&g.willDrawCell.push(a.willDrawCell),a.didDrawCell&&g.didDrawCell.push(a.didDrawCell),a.didDrawPage&&g.didDrawPage.push(a.didDrawPage)}return g}(g,h,i),R=function n(o,i){var f,h,g,y,S,a,D,R,O,M,p,b,C,l=(0,v.parseSpacing)(i.margin,40/o.scaleFactor()),P=null!==(f=function t(o,i){var f=o.getLastAutoTable(),h=o.scaleFactor(),g=o.pageNumber(),y=!1;return f&&f.startPageNumber&&(y=f.startPageNumber+f.pageNumber-1===g),"number"==typeof i?i:null!=i&&!1!==i||!y||null==f?.finalY?null:f.finalY+20/h}(o,i.startY))&&void 0!==f?f:l.top;b=!0===i.showFoot?"everyPage":!1===i.showFoot?"never":null!==(h=i.showFoot)&&void 0!==h?h:"everyPage",C=!0===i.showHead?"everyPage":!1===i.showHead?"never":null!==(g=i.showHead)&&void 0!==g?g:"everyPage";var T=null!==(y=i.useCss)&&void 0!==y&&y,F=null!==(S=i.horizontalPageBreakRepeat)&&void 0!==S?S:null;return{includeHiddenHtml:null!==(a=i.includeHiddenHtml)&&void 0!==a&&a,useCss:T,theme:i.theme||(T?"plain":"striped"),startY:P,margin:l,pageBreak:null!==(D=i.pageBreak)&&void 0!==D?D:"auto",rowPageBreak:null!==(R=i.rowPageBreak)&&void 0!==R?R:"auto",tableWidth:null!==(O=i.tableWidth)&&void 0!==O?O:"auto",showHead:C,showFoot:b,tableLineWidth:null!==(M=i.tableLineWidth)&&void 0!==M?M:0,tableLineColor:null!==(p=i.tableLineColor)&&void 0!==p?p:200,horizontalPageBreak:!!i.horizontalPageBreak,horizontalPageBreakRepeat:F}}(f,y),O=function e(o,i,f){var h=i.head||[],g=i.body||[],y=i.foot||[];if(i.html)if(f){var a=(0,x.parseHtml)(o,i.html,f,i.includeHiddenHtml,i.useCss)||{};h=a.head||h,g=a.body||h,y=a.foot||h}else console.error("Cannot parse html in non browser environment");return{columns:i.columns||r(h,g,y),head:h,body:g,foot:y}}(f,y,S);return{id:i.tableId,content:O,hooks:D,styles:a,settings:R}}},291:function(A,m){function x(H){H.rowHeight?(console.error("Use of deprecated style rowHeight. It is renamed to minCellHeight."),H.minCellHeight||(H.minCellHeight=H.rowHeight)):H.columnWidth&&(console.error("Use of deprecated style columnWidth. It is renamed to cellWidth."),H.cellWidth||(H.cellWidth=H.columnWidth))}Object.defineProperty(m,"__esModule",{value:!0}),m.default=function W(H,v,s,c){for(var w=function(t){t&&"object"!=typeof t&&console.error("The options parameter should be of type object, is: "+typeof t),typeof t.extendWidth<"u"&&(t.tableWidth=t.extendWidth?"auto":"wrap",console.error("Use of deprecated option: extendWidth, use tableWidth instead.")),typeof t.margins<"u"&&(typeof t.margin>"u"&&(t.margin=t.margins),console.error("Use of deprecated option: margins, use margin instead.")),t.startY&&"number"!=typeof t.startY&&(console.error("Invalid value for startY option",t.startY),delete t.startY),!t.didDrawPage&&(t.afterPageContent||t.beforePageContent||t.afterPageAdd)&&(console.error("The afterPageContent, beforePageContent and afterPageAdd hooks are deprecated. Use didDrawPage instead"),t.didDrawPage=function(y){H.applyStyles(H.userStyles),t.beforePageContent&&t.beforePageContent(y),H.applyStyles(H.userStyles),t.afterPageContent&&t.afterPageContent(y),H.applyStyles(H.userStyles),t.afterPageAdd&&y.pageNumber>1&&y.afterPageAdd(y),H.applyStyles(H.userStyles)}),["createdHeaderCell","drawHeaderRow","drawRow","drawHeaderCell"].forEach(function(y){t[y]&&console.error('The "'.concat(y,'" hook has changed in version 3.0, check the changelog for how to migrate.'))}),[["showFoot","showFooter"],["showHead","showHeader"],["didDrawPage","addPageContent"],["didParseCell","createdCell"],["headStyles","headerStyles"]].forEach(function(y){var S=y[0],a=y[1];t[a]&&(console.error("Use of deprecated option ".concat(a,". Use ").concat(S," instead")),t[S]=t[a])}),[["padding","cellPadding"],["lineHeight","rowHeight"],"fontSize","overflow"].forEach(function(y){var S="string"==typeof y?y:y[0],a="string"==typeof y?y:y[1];typeof t[S]<"u"&&(typeof t.styles[a]>"u"&&(t.styles[a]=t[S]),console.error("Use of deprecated option: "+S+", use the style "+a+" instead."))});for(var e=0,r=["styles","bodyStyles","headStyles","footStyles"];e<r.length;e++)x(t[r[e]]||{});for(var i=t.columnStyles||{},f=0,h=Object.keys(i);f<h.length;f++)x(i[h[f]]||{})},d=0,u=[v,s,c];d<u.length;d++)w(u[d])}},287:function(A,m,W){Object.defineProperty(m,"__esModule",{value:!0}),m.Column=m.Cell=m.Row=m.Table=void 0;var x=W(913),H=W(662),v=W(200),s=function(){function u(n,t){this.pageNumber=1,this.pageCount=1,this.id=n.id,this.settings=n.settings,this.styles=n.styles,this.hooks=n.hooks,this.columns=t.columns,this.head=t.head,this.body=t.body,this.foot=t.foot}return u.prototype.getHeadHeight=function(n){return this.head.reduce(function(t,e){return t+e.getMaxCellHeight(n)},0)},u.prototype.getFootHeight=function(n){return this.foot.reduce(function(t,e){return t+e.getMaxCellHeight(n)},0)},u.prototype.allRows=function(){return this.head.concat(this.body).concat(this.foot)},u.prototype.callCellHooks=function(n,t,e,r,o,i){for(var f=0,h=t;f<h.length;f++){var S=!1===(0,h[f])(new H.CellHookData(n,this,e,r,o,i));if(e.text=Array.isArray(e.text)?e.text:[e.text],S)return!1}return!0},u.prototype.callEndPageHooks=function(n,t){n.applyStyles(n.userStyles);for(var e=0,r=this.hooks.didDrawPage;e<r.length;e++)(0,r[e])(new H.HookData(n,this,t))},u.prototype.getWidth=function(n){if("number"==typeof this.settings.tableWidth)return this.settings.tableWidth;if("wrap"===this.settings.tableWidth)return this.columns.reduce(function(r,o){return r+o.wrappedWidth},0);var e=this.settings.margin;return n-e.left-e.right},u}();m.Table=s;var c=function(){function u(n,t,e,r,o){void 0===o&&(o=!1),this.height=0,this.raw=n,n instanceof x.HtmlRowInput&&(this.raw=n._element,this.element=n._element),this.index=t,this.section=e,this.cells=r,this.spansMultiplePages=o}return u.prototype.getMaxCellHeight=function(n){var t=this;return n.reduce(function(e,r){var o;return Math.max(e,(null===(o=t.cells[r.index])||void 0===o?void 0:o.height)||0)},0)},u.prototype.hasRowSpan=function(n){var t=this;return n.filter(function(e){var r=t.cells[e.index];return!!r&&r.rowSpan>1}).length>0},u.prototype.canEntireRowFit=function(n,t){return this.getMaxCellHeight(t)<=n},u.prototype.getMinimumRowHeight=function(n,t){var e=this;return n.reduce(function(r,o){var i=e.cells[o.index];if(!i)return 0;var f=i.styles.fontSize/t.scaleFactor()*x.FONT_ROW_RATIO,g=i.padding("vertical")+f;return g>r?g:r},0)},u}();m.Row=c;var w=function(){function u(n,t,e){var r,o;this.contentHeight=0,this.contentWidth=0,this.wrappedWidth=0,this.minReadableWidth=0,this.minWidth=0,this.width=0,this.height=0,this.x=0,this.y=0,this.styles=t,this.section=e,this.raw=n;var i=n;null==n||"object"!=typeof n||Array.isArray(n)?(this.rowSpan=1,this.colSpan=1):(this.rowSpan=n.rowSpan||1,this.colSpan=n.colSpan||1,i=null!==(o=null!==(r=n.content)&&void 0!==r?r:n.title)&&void 0!==o?o:n,n._element&&(this.raw=n._element)),this.text=(null!=i?""+i:"").split(/\r\n|\r|\n/g)}return u.prototype.getTextPos=function(){var n,e;if("top"===this.styles.valign)n=this.y+this.padding("top");else if("bottom"===this.styles.valign)n=this.y+this.height-this.padding("bottom");else{var t=this.height-this.padding("vertical");n=this.y+t/2+this.padding("top")}if("right"===this.styles.halign)e=this.x+this.width-this.padding("right");else if("center"===this.styles.halign){var r=this.width-this.padding("horizontal");e=this.x+r/2+this.padding("left")}else e=this.x+this.padding("left");return{x:e,y:n}},u.prototype.getContentHeight=function(n){var r=(Array.isArray(this.text)?this.text.length:1)*(this.styles.fontSize/n*x.FONT_ROW_RATIO)+this.padding("vertical");return Math.max(r,this.styles.minCellHeight)},u.prototype.padding=function(n){var t=(0,v.parseSpacing)(this.styles.cellPadding,0);return"vertical"===n?t.top+t.bottom:"horizontal"===n?t.left+t.right:t[n]},u}();m.Cell=w;var d=function(){function u(n,t,e){this.wrappedWidth=0,this.minReadableWidth=0,this.minWidth=0,this.width=0,this.dataKey=n,this.raw=t,this.index=e}return u.prototype.getMaxCustomCellWidth=function(n){for(var t=0,e=0,r=n.allRows();e<r.length;e++){var i=r[e].cells[this.index];i&&"number"==typeof i.styles.cellWidth&&(t=Math.max(t,i.styles.cellWidth))}return t},u}();m.Column=d},360:function(A,m){Object.defineProperty(m,"__esModule",{value:!0}),m.assign=void 0,m.assign=function W(x,H,v,s,c){if(null==x)throw new TypeError("Cannot convert undefined or null to object");for(var w=Object(x),d=1;d<arguments.length;d++){var u=arguments[d];if(null!=u)for(var n in u)Object.prototype.hasOwnProperty.call(u,n)&&(w[n]=u[n])}return w}},858:function(A,m,W){Object.defineProperty(m,"__esModule",{value:!0}),m.createTable=void 0;var x=W(323),H=W(287),v=W(189),s=W(913),c=W(360);function u(o,i,f,h,g,y){var S={};return i.map(function(D,R){for(var O=0,M={},p=0,l=0,P=0,b=f;P<b.length;P++){var C=b[P];if(null==S[C.index]||0===S[C.index].left)if(0===l){var T,k={};"object"==typeof(T=Array.isArray(D)?D[C.index-p-O]:D[C.dataKey])&&!Array.isArray(T)&&(k=T?.styles||{});var _=r(o,C,R,g,h,y,k),F=new H.Cell(T,_,o);M[C.dataKey]=F,M[C.index]=F,S[C.index]={left:F.rowSpan-1,times:l=F.colSpan-1}}else l--,p++;else S[C.index].left--,l=S[C.index].times,O++}return new H.Row(D,R,o,M)})}function n(o,i){var f={};return o.forEach(function(h){if(null!=h.raw){var g=function t(o,i){if("head"===o){if("object"==typeof i)return i.header||i.title||null;if("string"==typeof i||"number"==typeof i)return i}else if("foot"===o&&"object"==typeof i)return i.footer;return null}(i,h.raw);null!=g&&(f[h.dataKey]=g)}}),Object.keys(f).length>0?f:null}function r(o,i,f,h,g,y,S){var D,a=(0,s.getTheme)(h);"head"===o?D=g.headStyles:"body"===o?D=g.bodyStyles:"foot"===o&&(D=g.footStyles);var R=(0,c.assign)({},a.table,a[o],g.styles,D),M="body"===o&&(g.columnStyles[i.dataKey]||g.columnStyles[i.index])||{},p="body"===o&&f%2==0?(0,c.assign)({},a.alternateRow,g.alternateRowStyles):{},l=(0,s.defaultStyles)(y),P=(0,c.assign)({},l,R,p,M);return(0,c.assign)(P,S)}m.createTable=function w(o,i){var f=new x.DocHandler(o),h=function d(o,i){var g,f=o.content,h=function e(o){return o.map(function(i,f){var h,g,y;return y="object"==typeof i&&null!==(g=null!==(h=i.dataKey)&&void 0!==h?h:i.key)&&void 0!==g?g:f,new H.Column(y,i,f)})}(f.columns);0===f.head.length&&(g=n(h,"head"))&&f.head.push(g),0===f.foot.length&&(g=n(h,"foot"))&&f.foot.push(g);var y=o.settings.theme,S=o.styles;return{columns:h,head:u("head",f.head,h,S,y,i),body:u("body",f.body,h,S,y,i),foot:u("foot",f.foot,h,S,y,i)}}(i,f.scaleFactor()),g=new H.Table(i,h);return(0,v.calculateWidths)(f,g),f.applyStyles(f.userStyles),g}},49:function(A,m,W){var x=this&&this.__spreadArray||function(p,l,P){if(P||2===arguments.length)for(var T,b=0,C=l.length;b<C;b++)(T||!(b in l))&&(T||(T=Array.prototype.slice.call(l,0,b)),T[b]=l[b]);return p.concat(T||Array.prototype.slice.call(l))};Object.defineProperty(m,"__esModule",{value:!0}),m.addPage=m.drawTable=void 0;var H=W(913),v=W(200),s=W(287),c=W(323),w=W(360),d=W(938),u=W(435);function i(p,l,P){var b=p.styles.fontSize/P.scaleFactor()*H.FONT_ROW_RATIO,C=p.padding("vertical"),T=Math.floor((l-C)/b);return Math.max(0,T)}function g(p,l,P,b,C,T,k){var _=function R(p,l,P,b){var C=l.settings.margin.bottom,T=l.settings.showFoot;return("everyPage"===T||"lastPage"===T&&P)&&(C+=l.getFootHeight(l.columns)),p.pageSize().height-b.y-C}(p,l,b,T);if(P.canEntireRowFit(_,k))y(p,l,P,T,k);else if(function h(p,l,P,b){var C=p.pageSize().height,T=b.settings.margin,_=C-(T.top+T.bottom);"body"===l.section&&(_-=b.getHeadHeight(b.columns)+b.getFootHeight(b.columns));var F=l.getMinimumRowHeight(b.columns,p),z=F<P;if(F>_)return console.error("Will not be able to print row ".concat(l.index," correctly since it's minimum height is larger than page height")),!0;if(!z)return!1;var j=l.hasRowSpan(b.columns);return l.getMaxCellHeight(b.columns)>_?(j&&console.error("The content of row ".concat(l.index," will not be drawn correctly since drawing rows with a height larger than the page height and has cells with rowspans is not supported.")),!0):!(j||"avoid"===b.settings.rowPageBreak)}(p,P,_,l)){var F=function f(p,l,P,b){var C={};p.spansMultiplePages=!0,p.height=0;for(var T=0,k=0,_=P.columns;k<_.length;k++)if(z=p.cells[(F=_[k]).index]){Array.isArray(z.text)||(z.text=[z.text]);var j=new s.Cell(z.raw,z.styles,z.section);(j=(0,w.assign)(j,z)).text=[];var B=i(z,l,b);z.text.length>B&&(j.text=z.text.splice(B,z.text.length));var I=b.scaleFactor();z.contentHeight=z.getContentHeight(I),z.contentHeight>=l&&(z.contentHeight=l,j.styles.minCellHeight-=l),z.contentHeight>p.height&&(p.height=z.contentHeight),j.contentHeight=j.getContentHeight(I),j.contentHeight>T&&(T=j.contentHeight),C[F.index]=j}var N=new s.Row(p.raw,-1,p.section,C,!0);N.height=T;for(var L=0,G=P.columns;L<G.length;L++){var F,z;(j=N.cells[(F=G[L]).index])&&(j.height=N.height),(z=p.cells[F.index])&&(z.height=p.height)}return N}(P,_,l,p);y(p,l,P,T,k),O(p,l,C,T,k),g(p,l,F,b,C,T,k)}else O(p,l,C,T,k),g(p,l,P,b,C,T,k)}function y(p,l,P,b,C){b.x=l.settings.margin.left;for(var T=0,k=C;T<k.length;T++){var _=k[T],F=P.cells[_.index];if(F)if(p.applyStyles(F.styles),F.x=b.x,F.y=b.y,!1!==l.callCellHooks(p,l.hooks.willDrawCell,F,P,_,b)){S(p,F,b);var j=F.getTextPos();(0,d.default)(F.text,j.x,j.y,{halign:F.styles.halign,valign:F.styles.valign,maxWidth:Math.ceil(F.width-F.padding("left")-F.padding("right"))},p.getDocument()),l.callCellHooks(p,l.hooks.didDrawCell,F,P,_,b),b.x+=_.width}else b.x+=_.width;else b.x+=_.width}b.y+=P.height}function S(p,l,P){var b=l.styles;if(p.getDocument().setFillColor(p.getDocument().getFillColor()),"number"==typeof b.lineWidth){var C=(0,v.getFillStyle)(b.lineWidth,b.fillColor);C&&p.rect(l.x,P.y,l.width,l.height,C)}else"object"==typeof b.lineWidth&&(function a(p,l,P,b){p.rect(l.x,P.y,l.width,l.height,!1===b?null:"string"!=typeof b?"F":b)}(p,l,P,b.fillColor),function D(p,l,P,b,C){var T,k,_,F;function z(j,B,I){var N;p.getDocument().setLineWidth(B),(N=p.getDocument()).line.apply(N,x(x([],j,!1),[(0,v.getFillStyle)(B,I)],!1))}C.top&&(T=P.x,_=P.x+l.width,C.right&&(_+=.5*C.right),C.left&&(T-=.5*C.left),z([T,k=P.y,_,F=P.y],C.top,b)),C.bottom&&(T=P.x,_=P.x+l.width,C.right&&(_+=.5*C.right),C.left&&(T-=.5*C.left),z([T,k=P.y+l.height,_,F=P.y+l.height],C.bottom,b)),C.left&&(k=P.y,F=P.y+l.height,C.top&&(k-=.5*C.top),C.bottom&&(F+=.5*C.bottom),z([T=P.x,k,_=P.x,F],C.left,b)),C.right&&(k=P.y,F=P.y+l.height,C.top&&(k-=.5*C.top),C.bottom&&(F+=.5*C.bottom),z([T=P.x+l.width,k,_=P.x+l.width,F],C.right,b))}(p,l,P,b.fillColor,b.lineWidth))}function O(p,l,P,b,C){void 0===C&&(C=[]),p.applyStyles(p.userStyles),"everyPage"===l.settings.showFoot&&l.foot.forEach(function(k){return y(p,l,k,b,C)}),l.callEndPageHooks(p,b);var T=l.settings.margin;(0,v.addTableBorder)(p,l,P,b),M(p),l.pageNumber++,l.pageCount++,b.x=T.left,b.y=T.top,P.y=T.top,"everyPage"===l.settings.showHead&&(l.head.forEach(function(k){return y(p,l,k,b,C)}),p.applyStyles(p.userStyles))}function M(p){var l=p.pageNumber();p.setPage(l+1),p.pageNumber()===l&&p.addPage()}m.drawTable=function n(p,l){var P=l.settings,b=P.startY,C=P.margin,T={x:C.left,y:b},k=l.getHeadHeight(l.columns)+l.getFootHeight(l.columns),_=b+C.bottom+k;"avoid"===P.pageBreak&&(_+=l.allRows().reduce(function(I,N){return I+N.height},0));var j=new c.DocHandler(p);("always"===P.pageBreak||null!=P.startY&&_>j.pageSize().height)&&(M(j),T.y=C.top);var B=(0,w.assign)({},T);l.startPageNumber=j.pageNumber(),!0===P.horizontalPageBreak?function t(p,l,P,b){u.default.calculateAllColumnsCanFitInPage(p,l).map(function(T,k){p.applyStyles(p.userStyles),k>0?O(p,l,P,b,T.columns):function e(p,l,P,b){var C=l.settings;p.applyStyles(p.userStyles),("firstPage"===C.showHead||"everyPage"===C.showHead)&&l.head.forEach(function(T){return y(p,l,T,P,b)})}(p,l,b,T.columns),function r(p,l,P,b,C){p.applyStyles(p.userStyles),l.body.forEach(function(T,k){g(p,l,T,k===l.body.length-1,P,b,C)})}(p,l,P,b,T.columns),function o(p,l,P,b){var C=l.settings;p.applyStyles(p.userStyles),("lastPage"===C.showFoot||"everyPage"===C.showFoot)&&l.foot.forEach(function(T){return y(p,l,T,P,b)})}(p,l,b,T.columns)})}(j,l,B,T):(j.applyStyles(j.userStyles),("firstPage"===P.showHead||"everyPage"===P.showHead)&&l.head.forEach(function(I){return y(j,l,I,T,l.columns)}),j.applyStyles(j.userStyles),l.body.forEach(function(I,N){g(j,l,I,N===l.body.length-1,B,T,l.columns)}),j.applyStyles(j.userStyles),("lastPage"===P.showFoot||"everyPage"===P.showFoot)&&l.foot.forEach(function(I){return y(j,l,I,T,l.columns)})),(0,v.addTableBorder)(j,l,B,T),l.callEndPageHooks(j,T),l.finalY=T.y,p.lastAutoTable=l,p.previousAutoTable=l,p.autoTable&&(p.autoTable.previous=l),j.applyStyles(j.userStyles)},m.addPage=O},435:function(A,m,W){Object.defineProperty(m,"__esModule",{value:!0});var x=W(200),H=function(c,w){var d=(0,x.parseSpacing)(w.settings.margin,0);return c.pageSize().width-(d.left+d.right)},v=function(c,w,d){void 0===d&&(d={});var n=H(c,w),t=w.settings.horizontalPageBreakRepeat,e=null,r=[],o=[],i=w.columns.length,f=d&&d.start?d.start:0;for(null!=t&&(e=w.columns.find(function(g){return g.dataKey===t||g.index===t}))&&(r.push(e.index),o.push(w.columns[e.index]),n-=e.wrappedWidth);f<i;)if(e?.index!==f){var h=w.columns[f].wrappedWidth;if(n<h){(0===f||f===d.start)&&(r.push(f),o.push(w.columns[f]));break}r.push(f),o.push(w.columns[f]),n-=h,f++}else f++;return{colIndexes:r,columns:o,lastIndex:f}};m.default={getColumnsCanFitInPage:v,calculateAllColumnsCanFitInPage:function(c,w){for(var d=[],u=0,n=w.columns.length;u<n;){var t=v(c,w,{start:0===u?0:u});t&&t.columns&&t.columns.length?(u=t.lastIndex,d.push(t)):u++}return d},getPageAvailableWidth:H}},189:function(A,m,W){Object.defineProperty(m,"__esModule",{value:!0}),m.ellipsize=m.resizeColumns=m.calculateWidths=void 0;var x=W(200),H=W(435);function c(e,r,o){for(var i=r,f=e.reduce(function(M,p){return M+p.wrappedWidth},0),h=0;h<e.length;h++){var g=e[h],a=g.width+i*(g.wrappedWidth/f),D=o(g),R=a<D?D:a;r-=R-g.width,g.width=R}if(r=Math.round(1e10*r)/1e10){var O=e.filter(function(M){return!(r<0)||M.width>o(M)});O.length&&(r=c(O,r,o))}return r}function n(e,r,o,i,f){return e.map(function(h){return function t(e,r,o,i,f){var h=1e4*i.scaleFactor();if((r=Math.ceil(r*h)/h)>=(0,x.getStringWidth)(e,o,i))return e;for(;r<(0,x.getStringWidth)(e+f,o,i)&&!(e.length<=1);)e=e.substring(0,e.length-1);return e.trim()+f}(h,r,o,i,f)})}m.calculateWidths=function v(e,r){!function s(e,r){var o=e.scaleFactor(),i=r.settings.horizontalPageBreak,f=H.default.getPageAvailableWidth(e,r);r.allRows().forEach(function(h){for(var g=0,y=r.columns;g<y.length;g++){var S=y[g],a=h.cells[S.index];if(a){r.callCellHooks(e,r.hooks.didParseCell,a,h,S,null);var R=a.padding("horizontal");a.contentWidth=(0,x.getStringWidth)(a.text,a.styles,e)+R;var O=(0,x.getStringWidth)(a.text.join(" ").split(/\s+/),a.styles,e);a.minReadableWidth=O+a.padding("horizontal"),"number"==typeof a.styles.cellWidth?(a.minWidth=a.styles.cellWidth,a.wrappedWidth=a.styles.cellWidth):"wrap"===a.styles.cellWidth||!0===i?a.contentWidth>f?(a.minWidth=f,a.wrappedWidth=f):(a.minWidth=a.contentWidth,a.wrappedWidth=a.contentWidth):(a.minWidth=a.styles.minCellWidth||10/o,a.wrappedWidth=a.contentWidth,a.minWidth>a.wrappedWidth&&(a.wrappedWidth=a.minWidth))}}}),r.allRows().forEach(function(h){for(var g=0,y=r.columns;g<y.length;g++){var S=y[g],a=h.cells[S.index];if(a&&1===a.colSpan)S.wrappedWidth=Math.max(S.wrappedWidth,a.wrappedWidth),S.minWidth=Math.max(S.minWidth,a.minWidth),S.minReadableWidth=Math.max(S.minReadableWidth,a.minReadableWidth);else{var D=r.styles.columnStyles[S.dataKey]||r.styles.columnStyles[S.index]||{},R=D.cellWidth||D.minCellWidth;R&&"number"==typeof R&&(S.minWidth=R,S.wrappedWidth=R)}a&&(a.colSpan>1&&!S.minWidth&&(S.minWidth=a.minWidth),a.colSpan>1&&!S.wrappedWidth&&(S.wrappedWidth=a.minWidth))}})}(e,r);var o=[],i=0;r.columns.forEach(function(h){var g=h.getMaxCustomCellWidth(r);g?h.width=g:(h.width=h.wrappedWidth,o.push(h)),i+=h.width});var f=r.getWidth(e.pageSize().width)-i;f&&(f=c(o,f,function(h){return Math.max(h.minReadableWidth,h.minWidth)})),f&&(f=c(o,f,function(h){return h.minWidth})),f=Math.abs(f),!r.settings.horizontalPageBreak&&f>.1/e.scaleFactor()&&(f=f<1?f:Math.round(f),console.error("Of the table content, ".concat(f," units width could not fit page"))),function d(e){for(var r=e.allRows(),o=0;o<r.length;o++)for(var i=r[o],f=null,h=0,g=0,y=0;y<e.columns.length;y++){var S=e.columns[y];if((g-=1)>1&&e.columns[y+1])h+=S.width,delete i.cells[S.index];else if(f){var a=f;delete i.cells[S.index],f=null,a.width=S.width+h}else{if(!(a=i.cells[S.index]))continue;if(g=a.colSpan,h=0,a.colSpan>1){f=a,h+=S.width;continue}a.width=S.width+h}}}(r),function u(e,r){for(var o={count:0,height:0},i=0,f=e.allRows();i<f.length;i++){for(var h=f[i],g=0,y=e.columns;g<y.length;g++){var a=h.cells[y[g].index];if(a){r.applyStyles(a.styles,!0);var D=a.width-a.padding("horizontal");if("linebreak"===a.styles.overflow)a.text=r.splitTextToSize(a.text,D+1/r.scaleFactor(),{fontSize:a.styles.fontSize});else if("ellipsize"===a.styles.overflow)a.text=n(a.text,D,a.styles,r,"...");else if("hidden"===a.styles.overflow)a.text=n(a.text,D,a.styles,r,"");else if("function"==typeof a.styles.overflow){var R=a.styles.overflow(a.text,D);a.text="string"==typeof R?[R]:R}a.contentHeight=a.getContentHeight(r.scaleFactor());var O=a.contentHeight/a.rowSpan;a.rowSpan>1&&o.count*o.height<O*a.rowSpan?o={height:O,count:a.rowSpan}:o&&o.count>0&&o.height>O&&(O=o.height),O>h.height&&(h.height=O)}}o.count--}}(r,e),function w(e){for(var r={},o=1,i=e.allRows(),f=0;f<i.length;f++)for(var h=i[f],g=0,y=e.columns;g<y.length;g++){var S=y[g],a=r[S.index];if(o>1)o--,delete h.cells[S.index];else if(a)a.cell.height+=h.height,o=a.cell.colSpan,delete h.cells[S.index],a.left--,a.left<=1&&delete r[S.index];else{var D=h.cells[S.index];if(!D)continue;if(D.height=h.height,D.rowSpan>1){var R=i.length-f;r[S.index]={cell:D,left:D.rowSpan>R?R:D.rowSpan,row:h}}}}}(r)},m.resizeColumns=c,m.ellipsize=n},84:function(A){if(typeof Y>"u"){var m=new Error("Cannot find module 'undefined'");throw m.code="MODULE_NOT_FOUND",m}A.exports=Y}},U={};function E(A){var m=U[A];if(void 0!==m)return m.exports;var W=U[A]={exports:{}};return J[A].call(W.exports,W,W.exports,E),W.exports}var K={};return function(){var A=K;Object.defineProperty(A,"__esModule",{value:!0}),A.Cell=A.Column=A.Row=A.Table=A.CellHookData=A.__drawTable=A.__createTable=A.applyPlugin=void 0;var m=E(790),W=E(587),x=E(49),H=E(858),v=E(287);Object.defineProperty(A,"Table",{enumerable:!0,get:function(){return v.Table}});var s=E(662);Object.defineProperty(A,"CellHookData",{enumerable:!0,get:function(){return s.CellHookData}});var c=E(287);function w(e){(0,m.default)(e)}Object.defineProperty(A,"Cell",{enumerable:!0,get:function(){return c.Cell}}),Object.defineProperty(A,"Column",{enumerable:!0,get:function(){return c.Column}}),Object.defineProperty(A,"Row",{enumerable:!0,get:function(){return c.Row}}),A.applyPlugin=w,A.__createTable=function u(e,r){var o=(0,W.parseInput)(e,r);return(0,H.createTable)(e,o)},A.__drawTable=function n(e,r){(0,x.drawTable)(e,r)};try{var t=E(84);t.jsPDF&&(t=t.jsPDF),w(t)}catch{}A.default=function d(e,r){var o=(0,W.parseInput)(e,r),i=(0,H.createTable)(e,o);(0,x.drawTable)(e,i)}}(),K}())}}]);