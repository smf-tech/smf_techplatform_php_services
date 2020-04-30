
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
    
 function backButtonfunction()
        {
            window.location.href = "http://13.235.105.204/api/vanDetailsList";
        }  

</script>
</head>
<body >
<div class="container">
    
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">

                <div class="panel-body">
                        <div id="showMsg" class="alert-warning">
        
                        </div>
                        <div id="flashmsg" class="alert-success">{{$msg ?? ""}}</div>
                        <div  class="form-group">
                            <input type="button" class="btn btn-success" value="Back to Van List" onclick="backButtonfunction()"/>
                        </div>
                        <h3>Insert Depot</h3>
                    <form action="http://13.235.105.204/api/insertDepot" method="post">
                          
                        <legend></legend>

                            
                            <div class="form-group">
                                 <label for="depot_name">Depot Name </label>
                                <input type="text" id="depot_name" name="depot_name" placeholder="Depot Name" class="form-control" required = "required" pattern="[a-zA-Z][a-zA-Z0-9\s]*" oninvalid="this.setCustomValidity('Please Enter Valid Depot Name')" oninput="setCustomValidity('')"/>
                             </div>
                             
                            <br/>
                            <input type="submit" class="btn btn-success"/>
                         </form>                        
                </div>
            </div>
        </div>
    </div>
</div>
</body>
 
</html>
