(self.webpackChunk=self.webpackChunk||[]).push([[1779],{1324:(e,t,n)=>{var i=n(9755);n(9826),n(1539),n(9554),n(4747);var r=Swal.mixin({toast:!0,position:"top-end",showConfirmButton:!1,timer:3e3,timerProgressBar:!0,didOpen:function(e){e.addEventListener("mouseenter",Swal.stopTimer),e.addEventListener("mouseleave",Swal.resumeTimer)}});i("#salle_pointeuse").on("change",(function(){var e=i(this).val();i(".loader").show(),i.ajax({type:"POST",url:"/api/pointeuse_aff/"+e,success:function(e){i(".loader").hide(),i.fn.DataTable.isDataTable("#dtDynamicVerticalScrollExample_pointeuse")&&i("#dtDynamicVerticalScrollExample_pointeuse").DataTable().clear().destroy(),i("#dtDynamicVerticalScrollExample_pointeuse").html(e).DataTable({bLengthChange:!1,lengthMenu:[[11,25,35,50,100,2e13],[10,15,25,50,100,"All"]],"font-size":"3rem"})}})})),i("body #dtDynamicVerticalScrollExample_pointeuse").on("click","tr",(function(){var e=i(this).hasClass("highlighty");if(i("body #dtDynamicVerticalScrollExample_pointeuse tr").removeClass("highlighty"),i("body #dtDynamicVerticalScrollExample_pointeuse tr").removeClass("odd"),i("body #dtDynamicVerticalScrollExample_pointeuse tr").removeClass("even"),!e){i(this).addClass("highlighty");var t=i(this).closest("tr");list_pointeuse=[],list_pointeuse.push({sn:t.find("td:eq(1)").html(),ip:t.find("td:eq(2)").html()})}})),i("body #connect_pointeuse").on("click",(function(){list_pointeuse.forEach((function(e){i.ajax({type:"POST",url:"/api/pointeuse_connect/"+e.ip,success:function(e){"true"==e?r.fire({icon:"success",title:"Pointeuse connected"}):r.fire({icon:"error",title:"pointeuse not connected"})}})}))})),i("#att_pointeuse").on("click",(function(){var e=i("#datetime_pointeuse").val();list_pointeuse.forEach((function(t){i.ajax({type:"POST",url:"/api/pointeuse_att/"+t.ip+"/"+e,success:function(e){i.fn.DataTable.isDataTable("#dtDynamicVerticalScrollExample_pointeuse2")&&i("#dtDynamicVerticalScrollExample_pointeuse2").DataTable().clear().destroy(),i("#dtDynamicVerticalScrollExample_pointeuse2").html(e).DataTable({bLengthChange:!1,lengthMenu:[[11,25,35,50,100,2e13],[10,15,25,50,100,"All"]],"font-size":"3rem"})}})}))})),i("#user_pointeuse").on("click",(function(){list_pointeuse.forEach((function(e){i.ajax({type:"POST",url:"/api/pointeuse_user/"+e.ip,success:function(e){i.fn.DataTable.isDataTable("#dtDynamicVerticalScrollExample_pointeuse2")&&i("#dtDynamicVerticalScrollExample_pointeuse2").DataTable().clear().destroy(),i("#dtDynamicVerticalScrollExample_pointeuse2").html(e).DataTable({bLengthChange:!1,lengthMenu:[[11,25,35,50,100,2e13],[10,15,25,50,100,"All"]],"font-size":"3rem"})}})}))})),i("#download_pointeuse").on("click",(function(){var e=i("#datetime_pointeuse").val();list_pointeuse.forEach((function(t){i.ajax({type:"POST",url:"/api/pointeuse_download/"+t.ip+"/"+e,success:function(e){r.fire({icon:"success",title:"Pointeuse connected"})}})}))}))},1223:(e,t,n)=>{var i=n(5112),r=n(30),o=n(3070),a=i("unscopables"),c=Array.prototype;null==c[a]&&o.f(c,a,{configurable:!0,value:r(null)}),e.exports=function(e){c[a][e]=!0}},8533:(e,t,n)=>{"use strict";var i=n(2092).forEach,r=n(9341)("forEach");e.exports=r?[].forEach:function(e){return i(this,e,arguments.length>1?arguments[1]:void 0)}},2092:(e,t,n)=>{var i=n(9974),r=n(1702),o=n(8361),a=n(7908),c=n(6244),s=n(5417),l=r([].push),u=function(e){var t=1==e,n=2==e,r=3==e,u=4==e,p=6==e,f=7==e,d=5==e||p;return function(h,m,y,v){for(var S,x,L=a(h),T=o(L),b=i(m,y),_=c(T),g=0,E=v||s,D=t?E(h,_):n||f?E(h,0):void 0;_>g;g++)if((d||g in T)&&(x=b(S=T[g],g,L),e))if(t)D[g]=x;else if(x)switch(e){case 3:return!0;case 5:return S;case 6:return g;case 2:l(D,S)}else switch(e){case 4:return!1;case 7:l(D,S)}return p?-1:r||u?u:D}};e.exports={forEach:u(0),map:u(1),filter:u(2),some:u(3),every:u(4),find:u(5),findIndex:u(6),filterReject:u(7)}},9341:(e,t,n)=>{"use strict";var i=n(7293);e.exports=function(e,t){var n=[][e];return!!n&&i((function(){n.call(null,t||function(){throw 1},1)}))}},7475:(e,t,n)=>{var i=n(7854),r=n(3157),o=n(4411),a=n(111),c=n(5112)("species"),s=i.Array;e.exports=function(e){var t;return r(e)&&(t=e.constructor,(o(t)&&(t===s||r(t.prototype))||a(t)&&null===(t=t[c]))&&(t=void 0)),void 0===t?s:t}},5417:(e,t,n)=>{var i=n(7475);e.exports=function(e,t){return new(i(e))(0===t?0:t)}},8324:e=>{e.exports={CSSRuleList:0,CSSStyleDeclaration:0,CSSValueList:0,ClientRectList:0,DOMRectList:0,DOMStringList:0,DOMTokenList:1,DataTransferItemList:0,FileList:0,HTMLAllCollection:0,HTMLCollection:0,HTMLFormElement:0,HTMLSelectElement:0,MediaList:0,MimeTypeArray:0,NamedNodeMap:0,NodeList:1,PaintRequestList:0,Plugin:0,PluginArray:0,SVGLengthList:0,SVGNumberList:0,SVGPathSegList:0,SVGPointList:0,SVGStringList:0,SVGTransformList:0,SourceBufferList:0,StyleSheetList:0,TextTrackCueList:0,TextTrackList:0,TouchList:0}},8509:(e,t,n)=>{var i=n(317)("span").classList,r=i&&i.constructor&&i.constructor.prototype;e.exports=r===Object.prototype?void 0:r},3157:(e,t,n)=>{var i=n(4326);e.exports=Array.isArray||function(e){return"Array"==i(e)}},30:(e,t,n)=>{var i,r=n(9670),o=n(6048),a=n(748),c=n(3501),s=n(490),l=n(317),u=n(6200),p=u("IE_PROTO"),f=function(){},d=function(e){return"<script>"+e+"</"+"script>"},h=function(e){e.write(d("")),e.close();var t=e.parentWindow.Object;return e=null,t},m=function(){try{i=new ActiveXObject("htmlfile")}catch(e){}var e,t;m="undefined"!=typeof document?document.domain&&i?h(i):((t=l("iframe")).style.display="none",s.appendChild(t),t.src=String("javascript:"),(e=t.contentWindow.document).open(),e.write(d("document.F=Object")),e.close(),e.F):h(i);for(var n=a.length;n--;)delete m.prototype[a[n]];return m()};c[p]=!0,e.exports=Object.create||function(e,t){var n;return null!==e?(f.prototype=r(e),n=new f,f.prototype=null,n[p]=e):n=m(),void 0===t?n:o(n,t)}},6048:(e,t,n)=>{var i=n(9781),r=n(3070),o=n(9670),a=n(5656),c=n(1956);e.exports=i?Object.defineProperties:function(e,t){o(e);for(var n,i=a(t),s=c(t),l=s.length,u=0;l>u;)r.f(e,n=s[u++],i[n]);return e}},1956:(e,t,n)=>{var i=n(6324),r=n(748);e.exports=Object.keys||function(e){return i(e,r)}},9826:(e,t,n)=>{"use strict";var i=n(2109),r=n(2092).find,o=n(1223),a="find",c=!0;a in[]&&Array(1).find((function(){c=!1})),i({target:"Array",proto:!0,forced:c},{find:function(e){return r(this,e,arguments.length>1?arguments[1]:void 0)}}),o(a)},9554:(e,t,n)=>{"use strict";var i=n(2109),r=n(8533);i({target:"Array",proto:!0,forced:[].forEach!=r},{forEach:r})},4747:(e,t,n)=>{var i=n(7854),r=n(8324),o=n(8509),a=n(8533),c=n(8880),s=function(e){if(e&&e.forEach!==a)try{c(e,"forEach",a)}catch(t){e.forEach=a}};for(var l in r)r[l]&&s(i[l]&&i[l].prototype);s(o)}},e=>{e.O(0,[9755,6983],(()=>{return t=1324,e(e.s=t);var t}));e.O()}]);