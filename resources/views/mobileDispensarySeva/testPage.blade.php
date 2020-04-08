
<html>
 
<head>
 
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
    <meta charset="utf-8">
   <style type="text/css">
    @font-face {
           
            font-family: 'Noto Sans', sans-serif !important;
            font-weight: normal;
            src: url('http://13.235.105.204/fonts/NotoSans-Regular.ttf') format('truetype');
        }
     
       

       body,html{padding:0; margin:0.0rem;height:100%;width:100%;}
        body{
           /* background: url('http://13.235.105.204/images/2019_MV_Certificates_11_12_2019_Final_Prerak.png');*/
            font-family: 'Noto Sans', sans-serif !important; 
            font-size: 2em !important;
            line-height: 2em !important; 
        }

        #flashmsg {
            color: 
        }

    /* .... Other css rules here ... */

</style>
<script type="text/javascript">
    
    function createCookie(cookieName,cookieValue,hoursToExpire)
        {
          var date = new Date();
         
          date.setTime(date.getTime()+(hoursToExpire*60*60*1000));
          document.cookie = cookieName + "=" + cookieValue + "; expires=" + date.toGMTString();
        }
    function accessCookie(cookieName)
        {
          var name = cookieName + "=";
          var allCookieArray = document.cookie.split(';');
          for(var i=0; i<allCookieArray.length; i++)
          {
            var temp = allCookieArray[i].trim();
            if (temp.indexOf(name)==0)
            return temp.substring(name.length,temp.length);
          }
          return "";
        }    

    function checkCookie()
    {

        var cookieName = accessCookie("cookieName");
        var testValue = '34234';
        alert(cookieName);
        console.log(cookieName);
        if(cookieName=='')
        {

            createCookie("cookieName", testValue, 1);
        }


    }    
    
</script>
</head>
<body onload="checkCookie()">
<div class="container">
    
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">

                <div class="panel-body">
                        <div id="showMsg" class="alert-warning">
        
                        </div>
                        <div id="flashmsg" class="alert-success">{{$msg ?? ""}}</div>
                        <div  class="form-group">
                            <input type="button" class="btn btn-success" value="Back" onclick="backButtonfunction()"/>
                        </div>
                        <h3>Patient Information Van</h3>
                    
                </div>
            </div>
        </div>
    </div>
</div>
</body>
 
</html>
