<!doctype html>
<html lang="en">
<head>
	
    <meta charset="UTF-8">
    <title>Meet PDF :Four </title>
    <link href='http://fonts.googleapis.com/css?family=Roboto' rel='stylesheet'>
    <style type="text/css">
        body,html{padding:0; margin:0;height:100%;width:100%;}

        body {
            background: url('http://13.235.124.3/images/background.png');
              
            background-repeat: no-repeat; 
            background-position: top 0px left 0px;
            font-family: 'Roboto' !important;
 			font-size: 0.9em !important;
 			line-height: 1.15em !important;

        }


            .part1 {
                width: 40%  !important;
                height: 47%;
                float: left;
                border: 0px solid #000000;
                /*background-color: red;*/
            }
           

            .content{
                width: 100%;
                position: relative;
               
                
            }

            .part1 td{
                vertical-align: top !important;

            }
            .part1 tr{
                /*line-height: 40px;*/

            }
            .main_table{
                 padding-top: 40px;   
                 margin: 15px !important;
                 border:   0px solid Red;
            }

            .seprator{
            	width: 5px !important;
            }


                .userName_header
                {
                   
                    background-color: #1565c0;
                   
                    height: :15% !important;
                    text-align: center;
                    padding: 1.0rem 0rem 1.9rem 0rem;
                    margin: 0.4rem 0rem 0.6rem 0rem;
                     width:92%; 
                     font-family: 'Roboto' !important;
 					font-size: 1.3em !important;
 					          
                    color: #fff;
                    
                    background-color: #1565c0;
                    
                    position: relative;
                                        
                }
                .rightText
                    {
                        /*float: right;*/
                        margin-right:-17px;
                        color: #fff;
                                           }
                .leftText
                {
                    /*float: left;*/
                    margin-left:-17px;
                    color: #fff;
                    
                }
            img{
                margin-top:20px;
                
                vertical-align: top;
                width: 350px;
                height: 380px;
                border:2px solid #fff;
                border-radius:17px;
                margin-bottom:72px; 
            }
            .page-break {
                page-break-after: always;
                }
    </style>

</head>
<body>



<div class="content">

    
    <table class="main_table"  width="100%" border="0">
        <tr>
        @if(isset($userData['male']))

            @foreach($userData['male'] as $item) 
        
            <td class="part1" valign="top" >
                
                <div class="userName_header">
                    <span class="leftText">{{
                            $item['personal_details']['first_name'] }}
                                
                            {{ $item['personal_details']['last_name'] }}
                    </span>
                    <span class="rightText">({{ $item['badge']??'-'}})</span>  
                </div>
                <table border="0"  >

                        
                        
                        <tr>
                            <td width="20px">Height</td>
                            <td class="seprator">:</td>
                           <td width="140px" >{{ $item['personal_details']['height'] }}</td>
                            <td rowspan="9" width="460px;" align="center" valign="top" >
                                   @if($item['other_marital_information']['profile_image'])
                                        <img src="{{$item['profileImage']}}"> 
                                        @else 
                                         <img src="{{ base_path() }}/resources/assets/images/profile.png" />
                                        
                                    @endif  

                            </td>
                        </tr>

                        <tr>
                            <td >Weight</td>
                            <td class="seprator">:</td>
                            <td width="140px" >{{ $item['personal_details']['weight'] }}</td>
                        </tr>
                        <tr>
                            <td>Birth Date</td>
                            <td class="seprator">:</td>
                            <td width="140px">{{ $item['birthDate'] }}</td>
                        </tr>
                        <tr>
                            <td>Birth Time</td>
                            <td class="seprator">:</td>
                            <td width="140px">{{$item['personal_details']['birth_time'] }}</td>
                        </tr>
                        <tr>
                            <td>Birth Place</td>
                            <td class="seprator">:</td>
                            <td width="140px">{{ $item['personal_details']['birth_city'] }}</td>
                        </tr>
                        <tr>
                            <td>Blood Group</td>
                            <td class="seprator">:</td>
                            <td width="140px">{{ $item['personal_details']['blood_group'] }}</td>
                        </tr>
                        <tr>
                            <td>Horoscope Match</td>
                            <td class="seprator">:</td>
                            <td width="140px">{{ $item['personal_details']['match_patrika'] }}</td>
                        </tr>
                        <tr>
                            <td width="140px">Jain Section</td>
                            <td class="seprator">:</td>
                            <td>{{$item['personal_details']['sect']}}</td>
                        </tr>


                        <tr>
                            <td>Manglik</td>
                            <td class="seprator">:</td>
                            <td colspan="2">{{$item['personal_details']['is_manglik']}}</td>
                        </tr>
                        <tr>
                            <td>Education</td>
                            <td class="seprator">:</td>
                            <td colspan="2">
                                {{   $item['educational_details']['education_level'] }},
                                        {{$item['educational_details']['qualification_degree']}}
                            </td>
                        </tr>
                        <tr>
                            <td>Occupation</td>
                            <td class="seprator">:</td>
                            <td colspan="2">{{$item['occupational_details']['occupation']}}</td>
                        </tr>
                        <tr>
                            <td>Income</td>
                            <td class="seprator">:</td>
                            <td colspan="2">{{$item['educational_details']['income']}}</td>
                        </tr>
                        <tr>
                            <td>Cell No</td>
                            <td class="seprator">:</td>
                            <td colspan="2">{{$item['residential_details']['secondary_phone']}},
                                    {{$item['residential_details']['primary_phone']}}</td>
                        </tr>
                        <tr>
                            <td>Sakha Gotra</td>
                            <td class="seprator">:</td>
                            <td colspan="2">{{ 
                                        $item['family_details']['gotra']['self_gotra'] }}, 
                                       {{ $item['family_details']['gotra']['mama_gotra']}}, 
                                        {{$item['family_details']['gotra']['dada_gotra']}}, 
                                        {{$item['family_details']['gotra']['nana_gotra'] 

                                    }}
                            </td>
                        </tr>
                        <tr>
                            <td>Father's Name</td>
                            <td class="seprator">:</td>
                            <td colspan="2">{{ $item['family_details']['father_name'] }}</td>
                        </tr>
                        <tr>
                            <td>Mother's Name</td>
                            <td class="seprator">:</td>
                            <td colspan="2">{{ $item['family_details']['mother_name'] }}</td>
                        </tr>
                        <tr>
                            <td>Address</td>
                            <td class="seprator">:</td>
                            <td colspan="2">{{ $item['residential_details']['address'] }}</td>
                        </tr>
                    </table>
                
            </td>
            
        
             
                @if(((($loop->index)+1) %2)==0)
                    </tr>
                @elseif($loop->last)
                    <td class="part1" valign="top">&nbsp;</td>
                    
                @endif     
          @endforeach
        @endif





       
        
    </table>
</div>
 @if(isset($userData['female']))
    <div class="page-break"></div>
 @endif
<div class="content">

    
    <table class="main_table"  width="100%" border="0">
        <tr>
        @if(isset($userData['female']))

            @foreach($userData['female'] as $item) 
        
            <td class="part1" valign="top" >
                
                <div class="userName_header">
                    <span class="left">{{
                            $item['personal_details']['first_name'] }}
                                
                            {{ $item['personal_details']['last_name'] }}
                    </span>
                    <span class="right">({{ $item['badge']??'-'}})</span>  
                </div>
                <table border="0"  >

                        
                        
                        <tr>
                            <td width="20px">Height </td>
                            <td class="seprator">:</td>
                           <td width="140px" >{{ $item['personal_details']['height'] }}</td>
                            <td rowspan="9" width="460px;" align="center" valign="top" >
                                   @if($item['other_marital_information']['profile_image'])
                                        <img src="{{$item['profileImage']}}"> 
                                        @else 
                                         <img src="{{ base_path() }}/resources/assets/images/profile.png" />
                                        
                                    @endif  

                            </td>
                        </tr>

                        <tr>
                            <td >Weight</td>
                            <td class="seprator">:</td>
                            <td width="140px" >{{ $item['personal_details']['weight'] }}</td>
                        </tr>
                        <tr>
                            <td>Birth Date</td>
                            <td class="seprator">:</td>
                            <td width="140px">{{ $item['birthDate'] }}</td>
                        </tr>
                        <tr>
                            <td>Birth Time</td>
                            <td class="seprator">:</td>
                            <td width="140px">{{$item['personal_details']['birth_time'] }}</td>
                        </tr>
                        <tr>
                            <td>Birth Place</td>
                            <td class="seprator">:</td>
                            <td width="140px">{{ $item['personal_details']['birth_city'] }}</td>
                        </tr>
                        <tr>
                            <td>Blood Group</td>
                            <td class="seprator">:</td>
                            <td width="140px">{{ $item['personal_details']['blood_group'] }}</td>
                        </tr>
                        <tr>
                            <td>Horoscope Match</td>
                            <td class="seprator">:</td>
                            <td width="140px">{{ $item['personal_details']['match_patrika'] }}</td>
                        </tr>
                        <tr>
                            <td width="140px">Jain Section</td>
                            <td class="seprator">:</td>
                            <td>{{$item['personal_details']['sect']}}</td>
                        </tr>


                        <tr>
                            <td>Manglik</td>
                            <td class="seprator">:</td>
                            <td colspan="2">{{$item['personal_details']['is_manglik']}}</td>
                        </tr>
                        <tr>
                            <td>Education</td>
                            <td class="seprator">:</td>
                            <td colspan="2">
                                {{   $item['educational_details']['education_level'] }},
                                        {{$item['educational_details']['qualification_degree']}}
                            </td>
                        </tr>
                        <tr>
                            <td>Occupation</td>
                            <td class="seprator">:</td>
                            <td colspan="2">{{$item['occupational_details']['occupation']}}</td>
                        </tr>
                        <tr>
                            <td>Income</td>
                            <td class="seprator">:</td>
                            <td colspan="2">{{$item['educational_details']['income']}}</td>
                        </tr>
                        <tr>
                            <td>Cell No</td>
                            <td class="seprator">:</td>
                            <td colspan="2">{{$item['residential_details']['secondary_phone']}},
                                    {{$item['residential_details']['primary_phone']}}</td>
                        </tr>
                        <tr>
                            <td>Sakha Gotra</td>
                            <td class="seprator">:</td>
                            <td colspan="2">{{ 
                                        $item['family_details']['gotra']['self_gotra'] }}, 
                                       {{ $item['family_details']['gotra']['mama_gotra']}}, 
                                        {{$item['family_details']['gotra']['dada_gotra']}}, 
                                        {{$item['family_details']['gotra']['nana_gotra'] 

                                    }}
                            </td>
                        </tr>
                        <tr>
                            <td>Father's Name</td>
                            <td class="seprator">:</td>
                            <td colspan="2">{{ $item['family_details']['father_name'] }}</td>
                        </tr>
                        <tr>
                            <td>Mother's Name</td>
                            <td class="seprator">:</td>
                            <td colspan="2">{{ $item['family_details']['mother_name'] }}</td>
                        </tr>
                        <tr>
                            <td>Address</td>
                            <td class="seprator">:</td>
                            <td colspan="2">{{ $item['residential_details']['address'] }}</td>
                        </tr>
                    </table>
                
            </td>
            
        
             
                @if(((($loop->index)+1) %2)==0)
                    </tr>
                @elseif($loop->last)
                    <td class="part1" valign="top">&nbsp;</td>
                   
                @endif     
          @endforeach

        @endif




       
        
    </table>
</div>


</body>
</html>