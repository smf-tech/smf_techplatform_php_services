
<html>
 
<head>
 
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
    <meta charset="utf-8">
   <style type="text/css">
    #myOverlay{
		position:absolute;height:100%;width:100%;
	}
	#myOverlay{background:black;opacity:.7;z-index:2;display:none;}

	#loadingGIF{position:absolute;top:60%;left:25%;z-index:3;display:none;}

	button{margin:50px;height:60px;width:100px;}
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
        window.location.href = "http://13.235.105.204/api/selectVan";
    }

    //$("#flashmsg").hide("slow");
    setTimeout(function() {
        $('#flashmsg').fadeOut('slow');
    }, 6000); 

    setTimeout(function() {
        $('#errMsg').fadeOut('slow');
    }, 6000);
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
						<div id="errMsg" class="alert-warning">
						{{$errMsg ?? ""}}
                        </div>
                        <div id="flashmsg" class="alert-success">{{$msg ?? ""}}</div>
						<!-- loader start-->
							<div id="myOverlay"></div>
						<div id="loadingGIF"><img src="https://i.ya-webdesign.com/images/gif-loading-png-8.gif"/></div>
							<!-- loader End-->
                        <div  class="form-group">
                            <input type="button" class="btn btn-success" value="Back" onclick="backButtonfunction()"/>
                        </div>
                        <h3>Patients Register</h3>
                    <form onsubmit="return validate()" action="http://13.235.105.204/api/savePatientContactDetails" method="post" enctype="multipart/form-data">
                          <div id="container" style="display:none;width:5px;height:5px">

<img id="source_image"><img id="compressed_image">

						  </div>

                        <legend></legend>

                           
                             
                             <div class="form-group">
                                 <label for="patient_area">Area / स्थान</label>
                                 <input type="read" name="patient_area" placeholder="Pimple Gurav" class="form-control" required = "required" pattern="[a-zA-Z][a-zA-Z0-9\s]*" oninvalid="this.setCustomValidity('Please Enter Valid Area')" oninput="setCustomValidity('')"/>
                                
                             </div>
                             
                             <div class="form-group" id="PatientName" >
                                 <label for="patient_name">Patient Name / रोगी का नाम</label>
                                 <input type="text" required = "required" name="patient_name" placeholder="Patient Name" class="form-control"  oninvalid="this.setCustomValidity('Please Enter Valid Patient Name')" oninput="setCustomValidity('')"/>
                             </div>
                             <div class="form-group" id=">PatientMobileNo" >
                                 <label for="patient_phone">Patient Mobile No. / रोगी का मोबाइल नं.</label>
                                 <input type="text"  name="patient_phone" placeholder="Patient Mobile no." class="form-control" />
                             </div>
                             <div class="form-group" id="PatientAddress" >
                                 <label for="patient_address">Patient Address / रोगी का पता
</label>
                                 <textarea row="3" name="patient_address" placeholder="Patient address" class="form-control" /></textarea>
                             </div>

                             
                            <br/>
                            <div id="levelContainer"  class="form-group">
                                   
                            </div>
							
                            <input type="submit" id="submit" class="btn btn-success"/>
                         </form>                        
                </div>
            </div>
        </div>
    </div>
</div>
</body>
 

 <script type="text/javascript">
 
 function validate() {
document.getElementById("register_images_one").value = '';
document.getElementById("register_images_two").value = '';
document.getElementById("register_images_three").value = '';
document.getElementById("register_images_four").value = '';
document.getElementById("register_images_five").value = '';
$('#myOverlay').show();
$('#loadingGIF').show();
		
}	
document.getElementById("register_images_one").addEventListener("change", readFile, false);
document.getElementById("register_images_two").addEventListener("change", readFile, false);
document.getElementById("register_images_three").addEventListener("change", readFile, false);
document.getElementById("register_images_four").addEventListener("change", readFile, false);
document.getElementById("register_images_five").addEventListener("change", readFile, false);

var output_format = null;
	var file_name = null;
	  function readFile(evt) {
		      $('#myOverlay').show();
		$('#loadingGIF').show();
		

		var file = evt.target.files[0];
		var reader = new FileReader();
	    reader.onload = function(event) {
		/*	var image = new Image();
    image.src = event.target.result;
var ww = '';
			  image.onload = function(){
	                
	               // console.log("Image loaded");        
					//console.log(this.width+'******');
					ww = this.width;
	            }*/
					                //console.log("Image loaded"+ww);       
//									console.log(this.width);

			/*var img = document.createElement("IMG");
	            img.src = event.target.result;
				img.setAttribute("src", event.target.result);
				img.setAttribute("id", "source_imagei");
            */
				 /*var source_image = document.getElementById("source_image");
				 source_image.src = event.target.result;
   
	            source_image.onload = function(){
	                
	                console.log("Image loaded");
	            }*/
				
				var i = document.getElementById("source_image");
			//console.log(i);
	            i.src = event.target.result;
				
				 i.onload = function(){
	                
	                console.log("Image loaded");         
					
					
					
					
					
					
					
        var quality = 30;
       	
        console.log("process start...");
        console.log("process start compress ...");
				output_format = file.name.split(".").pop();

		var srcCompressed = jic.compress(source_image,quality,output_format,i.width,i.height).src;
		
	//	console.log(srcCompressed+'----+++++');
		var img = document.createElement("IMG");
		//img.src = event.target.result;
		img.setAttribute("id", "compressed_imagei");
	    
		img.setAttribute("src", srcCompressed);
		document.getElementById('container').appendChild(img);            

		var compressed_image = document.getElementById("compressed_image");
				compressed_image.src = srcCompressed;

        if (compressed_image.src == "") {
            alert("You must compress image first!");
            return false;
        }
		
	    var successCallback= function(response){
          //  console.log("image uploaded successfully! :)");
			
			var x = document.createElement("INPUT");
			x.setAttribute("type", "text");
			x.setAttribute("id", evt.target.id+'_1');
			x.setAttribute("name", evt.target.id+'_1');
			x.setAttribute("value", response);			
			document.getElementById('container').appendChild(x);		
            console.log(response);  
$('#myOverlay').hide();
		$('#loadingGIF').hide();
					
        }

        var errorCallback= function(response){
            console.log("image Filed to upload! :)");
            console.log(response); 
        }
		    	//console.log("---start upload ...");
		file_name = file.name;
		
		
    	jic.upload(compressed_image, "imageSevaUpload", "file", file_name,successCallback,errorCallback);
    	
    	console.log("process start upload ...");
					
					
					
					//console.log(this.width+'******');
					//ww = this.width;
	            }
			
	          
				console.log(i.width+'---width');
				
				//document.getElementById('container').appendChild(img);
			//	console.log('sorce file'+event.target.result)
				
				     if (i.src == "") {
            alert("You must load an image first!");
            //return false;
        }

        /*var quality = 30;
       	
        console.log("process start...");
        console.log("process start compress ...");
				output_format = file.name.split(".").pop();

		var srcCompressed = jic.compress(source_image,quality,output_format,i.width,i.height).src;
		
	//	console.log(srcCompressed+'----+++++');
		var img = document.createElement("IMG");
		//img.src = event.target.result;
		img.setAttribute("id", "compressed_imagei");
	    
		img.setAttribute("src", srcCompressed);
		document.getElementById('container').appendChild(img);            

		/*var compressed_image = document.getElementById("compressed_image");
				compressed_image.src = srcCompressed;

        if (compressed_image.src == "") {
            alert("You must compress image first!");
            return false;
        }*/
		
	 /*   var successCallback= function(response){
          //  console.log("image uploaded successfully! :)");
			
			var x = document.createElement("INPUT");
			x.setAttribute("type", "text");
			x.setAttribute("id", evt.target.id+'_1');
			x.setAttribute("name", evt.target.id+'_1');
			x.setAttribute("value", response);			
			document.getElementById('container').appendChild(x);		
            console.log(response);       
        }

        var errorCallback= function(response){
            console.log("image Filed to upload! :)");
            console.log(response); 
        }
		    	//console.log("---start upload ...");
		file_name = file.name;

    	jic.upload(compressed_image, "imageSevaUpload", "file", file_name,successCallback,errorCallback);
    	
    	console.log("process start upload ...");*/
    	
		

	    };
		output_format = file.name.split(".").pop();
	    console.log("Filename:" + file.name);
	    console.log("Fileformat:" + output_format);
	    console.log("Filesize:" + (parseInt(file.size) / 1024) + " Kb");
	    console.log("Type:" + file.type);
	    reader.readAsDataURL(file);
		
		
		//$("#compress").show();
	    return false;
	}

 </script>
 	<script src="/js/JIC.js"></script>

</html>
