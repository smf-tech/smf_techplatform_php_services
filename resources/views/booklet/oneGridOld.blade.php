<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Meet PDF</title>

    <style type="text/css">
        @page {
            margin: 10px;
        }

        body {
            margin: 10px;
        }

        * {
            font-size: 11pt;
            font-family: Verdana, Arial, sans-serif;
        }

        a {
            color: #fff;
            text-decoration: none;
        }

        table {
            font-size: x-small;
            border: 1px;
        }

        tfoot tr td {
            font-weight: bold;
            font-size: x-small;
            border: 1px solid black;
        }

        .invoice table {
           /* background-color: #c4c4c4;*/
            margin: 15px;
        }

        .invoice h3 {
            margin-left: 15px;
        }

        .information {
            background-color: #60A7A6;
            color: #FFF;
        }

        .information .logo {
            margin: 5px;
        }

        .textContent{
            /*background-color: #c4c4c4;*/
            margin-left: 0 !important;
            padding-right: 10px !important;
            width: 55% !important;
            border: 0px solid red;
        }
        .information table {
            padding: 10px;
        }
        .invoice img{
            margin-left: 10;
            left: 0; 
            vertical-align: top;
            position: absolute;
            width: 850px;
            height: 850px;
            border: 1px solid red;
        }

        .cardRow{

            margin-top:10px; 
        }

        .lable {
            
            color: #D2035B;
        }

        .text_bold_red {
            font-weight: bolder;
            color: #FB0404;
        }
        .text_bold_black {
            
            color: #000000;
        }
        .page-break {
        page-break-after: always;
        }
        
        .divider {
          border-bottom : 3px solid red ;
          height: 10px;
          margin: 10;
        }
    </style>

</head>
<body>

<div class="invoice">
    <!--h3>Invoice specification #123</h3-->
    <table width="100%" border="0px">
        <thead>
        <!--tr>
            <th>Description</th>
            <th>Quantity</th>
            <th>Total</th>
        </tr>
        </thead-->
        <tbody>
        @if(isset($userData['female']))    
            @foreach($userData['female'] as $item)    
            <tr class="cardRow">
                <td width="25%">
                    @if($item['other_marital_information']['profile_image'])
                       <img src="{{ $item['other_marital_information']['profile_image'] }}"> 
                        @else 
                        <img src="/assets/img/profile_no_image.png"> 
                        
                     @endif    
                </td>
                <td class="textContent" width="55%">
                    <table width="85%">
                        <tr>
                            <td class="lable" >First Name</td>
                            <td>:</td>
                            <td class="text_bold_red">{{ $item['personal_details']['first_name'] }}
                                        {{ $item['personal_details']['middle_name'] }}

                                        {{ $item['personal_details']['last_name'] }} 
                            </td>
                        </tr>
                        <tr>
                            <td class="lable">Address</td>
                            <td>:</td>
                            <td>{{ $item['residential_details']['address'] }}</td>
                        </tr>
                        <tr>
                            <td class="lable">Birthdate</td>
                            <td>:</td>
                            <td>{{ $item['birthDate'] }}</td>
                        </tr>
                        <tr>
                            <td class="lable">Birth Time</td>
                            <td>:</td>
                            <td>{{$item['personal_details']['birth_time'] }}</td>
                        </tr>
                        <tr>
                            <td class="lable">Birth Place</td>
                            <td>:</td>
                            <td>{{ $item['personal_details']['birth_city'] }}</td>
                        </tr>
                        <tr>
                            <td class="lable">Blood Group</td>
                            <td>:</td>
                            <td>{{ $item['personal_details']['blood_group'] }}</td>
                        </tr>
                        
                        <tr>
                            <td class="lable">Height</td>
                            <td>:</td>
                            <td>{{ $item['personal_details']['height'] }}</td>
                        </tr>
                        <tr>
                            <td class="lable">Weight</td>
                            <td>:</td>
                            <td>{{ $item['personal_details']['weight'] }}</td>
                        </tr>
                        <tr>
                            <td class="lable">Complexion</td>
                            <td>:</td>
                            <td>{{$item['personal_details']['complexion']}}</td>
                        </tr>
                        <tr>
                            <td class="lable">Manglik</td>
                            <td>:</td>
                            <td>{{$item['personal_details']['is_manglik']}}</td>
                        </tr>
                        <tr>
                            <td class="lable">Jain Sect</td>
                            <td>:</td>
                            <td>{{$item['personal_details']['sect']}}</td>
                        </tr>
                        <tr>
                            <td class="lable">Sub Sect</td>
                            <td>:</td>
                            <td>{{ $item['personal_details']['sub_cast']??'-' }}</td>
                        </tr>
                        <tr>
                            <td class="lable">Sakha-Gotra</td>
                            <td>:</td>
                            <td>{{ 
                                        $item['family_details']['gotra']['self_gotra'] }}, 
                                       {{ $item['family_details']['gotra']['mama_gotra']}}, 
                                        {{$item['family_details']['gotra']['dada_gotra']}}, 
                                        {{$item['family_details']['gotra']['nana_gotra'] 

                                    }}
                            </td>
                        </tr>
                        <tr>
                            <td class="lable">Education</td>
                            <td>:</td>
                            <td>{{   $item['educational_details']['education_level'] }},
                                        {{$item['educational_details']['qualification_degree']}}   
                            </td>
                        </tr>
                        <tr>
                            <td class="lable">Profession</td>
                            <td>:</td>
                            <td>{{$item['occupational_details']['occupation']}}</td>
                        </tr>
                        <tr>
                            <td class="lable">Annual Income</td>
                            <td>:</td>
                            <td>{{$item['educational_details']['income']}}</td>
                        </tr>
                        <tr>
                            <td class="lable">Cell No.</td>
                            <td>:</td>
                            <td>{{$item['residential_details']['secondary_phone']}},
                                    {{$item['residential_details']['primary_phone']}}
                            </td>
                        </tr>
                        <tr>
                            <td class="lable">About me</td>
                            <td>:</td>
                            <td>{{ $item['other_marital_information']['about_me'] }}</td>
                        </tr>
                        <tr>
                            <td class="lable">Expectation about Life  Partner</td>
                            <td>:</td>
                            <td>{{ $item['other_marital_information']['expectation_from_partner'] }}</td>
                        </tr>
                    </table>
                     

                </td>
                
            </tr>
            <tr>
                <td class="lable">Father's Name : <span class="text_bold_red">{{ $item['family_details']['father_name'] }}<span> </td>
                <td class="lable textContent">Father Occupation : <span class="text_bold_black"> {{ $item['family_details']['father_occupation'] }}</span></td>
            </tr>
            <tr>
                <td class="lable">Mother's Name : <span class="text_bold_red"> {{ $item['family_details']['mother_name'] }}</span> </td>
                <td class="lable textContent">Father Occupation : <span class="text_bold_black">{{ $item['family_details']['mother_occupation'] }}</span> </td>
            </tr>
            <tr>
                <td colspan="2" class="lable">Postal Address : <span class="text_bold_black">{{ $item['residential_details']['address'] }}</span></td>
            </tr>
            <tr >
                <td class="lable">Phone/Cell No.  : 
                        <span class="text_bold_black">{{$item['residential_details']['secondary_phone']}},
                        {{$item['residential_details']['primary_phone']}}</span></td>
                <td class="lable textContent">Email : <span class="text_bold_black">{{ $item['residential_details']['primary_email_address'] }}</span></td>
                
            </tr>

            <tr class="divider"><td colspan="2">&nbsp;&nbsp;</td></tr>
                    
            
            @endforeach
        @endif    
        
        </tbody>

       
    </table>
</div>

<!--div class="information" style="position: absolute; bottom: 0;">
    <table width="100%">
        <tr>
            <td align="left" style="width: 50%;">
                &copy; {{ date('Y') }} {{ config('app.url') }} - All rights reserved.
            </td>
            <td align="right" style="width: 50%;">
                Company Slogan
            </td>
        </tr>

    </table>
</div-->
</body>
</html>