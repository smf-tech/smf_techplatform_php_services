<!doctype html>
<html lang="en">
<head>

    <meta http-equiv="Content-Type" content="text/html" charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
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
        body,html{padding:0%; margin: 0;height:100%;width:100%;}
        body{
            background: url('http://13.235.105.204/images/2019_MV_Certificates_11_12_2019_Final_Shikshak.png') top center 100% 100% ;
            background-repeat: no-repeat !important; 
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
                top:42.4%;
                left:28%; 
               
            }

            .schoolName
            {
                font-size: 1.2em !important;
                line-height: 1.5em !important;
                margin-left:1%;
                position: absolute;
                top:46%;
                left:28%; 
               
            }

            .taluka
            {
                font-size: 1.2em !important;
                line-height: 1.5em !important;
                margin-left:1%;
                position: absolute;
                top:49.2%;
                left:24%; 
               
            }

            .district
            {
                font-size: 1.2em !important;
                line-height: 1.5em !important;
                margin-left:1%;
                position: absolute;
                top:49.2%;
                left:54.5%; 
               
            }

            .teacherTrainingDays
            {
                font-size: 1.2em !important;
                line-height: 1.5em !important;
                margin-left:1%;
                position: absolute;
                top:64.6%;
                left:25%; 
               
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

     <div class="teacherTrainingDays">
        {{ $UserData['teacherTrainingDays']}}
    </div>

    <div>
        
    </div>
    <div>

    </div>
        
         
        
    @endif  
     
</div>





</body>
</html>