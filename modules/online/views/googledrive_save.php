<!DOCTYPE HTML><html><head>

</head><body>fff
<div class="g-savetodrive" 
	data-src="https://co23.nevseoboi.com.ua/cats/336/33665/1690827847-af3adb7a-aa1e-4107-86f9-bad97c50a8a8.jpg"
	data-filename="picture.jpg"
	data-sitename="Pictures"
>
	
</div>

<script>
  var clientId = '720409271749-hqv3lb4u0q82t68vperem5oi3kosskol.apps.googleusercontent.com';
  var developerKey = 'AIzaSyBTEWsJ4aXdoOzB4ey81eX9-ja7HejL4Qc';
  var accessToken;
  function onApiLoad() {
    gapi.load('auth', authenticateWithGoogle);
    gapi.load('picker');
  }
  function authenticateWithGoogle() {
    window.gapi.auth.authorize({
      'client_id': clientId,
      'scope': ['https://www.googleapis.com/auth/drive']
    }, handleAuthentication);
  }
  function handleAuthentication(result) {
    if(result && !result.error) {
      accessToken = result.access_token;
      setupPicker();
    }
  }
  function setupPicker() {
    var picker = new google.picker.PickerBuilder()
      .setOAuthToken(accessToken)
      .setDeveloperKey(developerKey)
      .addView(new google.picker.DocsUploadView())
      .enableFeature(google.picker.Feature.NAV_HIDDEN)
      .enableFeature(google.picker.Feature.Feature.MULTISELECT_ENABLED)
      .setCallback(myCallback)
      .build();
    picker.setVisible(true);
  }
  function myCallback(data) {
    if (data.action == google.picker.Action.PICKED) {
      alert(data.docs[0].name);
    } else if (data.action == google.picker.Action.CANCEL) {
      alert('goodbye');
    }
  }
</script>
<script src="https://apis.google.com/js/api.js?onload=onApiLoad"></script>


</body></html>
<?php

/*
01 
<script src="https://apis.google.com/js/platform.js"></script>

02

 
 
 
*/
