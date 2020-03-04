<!doctype html>
<html lang="">
<head>

    <meta http-equiv="Content-Type" content="text/html" charset="UTF-8">
    <title> @if(isset($UserData['certificateType']))
                    {{$UserData['certificateType']}}
                @endif </title>
    <!--link href='http://fonts.googleapis.com/css?family=Roboto' rel='stylesheet'-->

<style type="text/css">
        @font-face {
            font-family: 'Noto Sans', sans-serif !important;
            font-weight: normal;
            src: url('http://13.235.105.204/fonts/NotoSans-Regular.ttf') format('truetype');
        }
        body {font-family:Noto Sans !important;}
        body,html{padding:0; margin:0.0rem;height:100%;width:100%;}
        body{
            background: url('http://13.235.105.204/images/2019_MV_Certificates_11_12_2019_Final_Anubhav.png');
            font-family: 'Noto Sans', sans-serif !important;
           /*font-family: 'Mukta', sans-serif;
            font-family: 'Hind', sans-serif;*/
            font-size: 0.9em !important;
            line-height: 1.15em !important;

        }


            .content{
                width: 100%;
                position: absolute;
                
            }

         .userName
            {
                font-size: 1.2em !important;
                line-height: 1.5em !important;
                margin-left:1%;
                position: absolute;
                top:43.7%;
                left:29%; 
               
            }

            .schoolName
            {
                font-size: 1.2em !important;
                line-height: 1.5em !important;
                margin-left:1%;
                position: absolute;
                top:47%;
                left:29.6%; 
               
            }


             .taluka
            {
                font-size: 1.2em !important;
                line-height: 1.5em !important;
                margin-left:1%;
                position: absolute;
                top:50.3%;
                left:26.5%; 
               
            }


            .district
            {
                font-size: 1.2em !important;
                line-height: 1.5em !important;
                margin-left:1%;
                position: absolute;
                top:50.3%;
                left:61.5%; 
               
            }


           

            
                    
            
            .page-break {
                page-break-after: always;
                }
    </style>

</head>
<body>


<div class="content">

    @if(isset($UserData)) 

    <div class="userName" >
        {{ $UserData['userName']}}              
    </div>   
    <div class="schoolName">
        {{ $UserData['schoolName']}}
    </div>

    <div class="district">
        {{ $UserData['district']}}
    </div>


    <div class="taluka">
        {{ $UserData['taluka']}}
    </div>

    <div>
        
    </div>
    <div>

    </div>
        
         
        
    @endif  
     
</div>





</body>
</html>