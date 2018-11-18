<html>
<head>
<title>IPAS Links</title>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no, minimal-ui, viewport-fit=cover">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
</head>
<body>


<script>


var url = "https://private-anon-d23ce3189e-ipswdownloads.apiary-proxy.com/v4/device/iPhone11%2C6?type=ipsw";


var xmlHttp = new XMLHttpRequest();


xmlHttp.onreadystatechange = function() {
  if (xmlHttp.readyState == 4 && xmlHttp.status == 200)


document.getElementById('ico').innerHTML = '<img src="' + JSON.parse(xmlHttp.responseText).data.TBAppIcon + '"><p>';

document.getElementById('link').innerHTML = '<a href="' + JSON.parse(xmlHttp.responseText).data.TBAppLink + '">Test install</a> <p>';

    document.getElementById('ipa').innerHTML = JSON.parse(xmlHttp.responseText).version + "<p>";

}

xmlHttp.open("GET", url, true);

xmlHttp.send();



</script>

</b>ipa link: <p>
<div id="ico"></div>
<div id="link"></div>
<div id="ipa"></div>




</body>
</html>