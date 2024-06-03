<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRelicensureCertificateSetting extends Migration
{
    public function up()
    {
        //insert into settings table
        $this->db->table("settings")->insertBatch([[
            "class" => "Practitioners",
            "key" => "relicensure_certificate_doctors",
            "value" => '<div class="good_standing_cert watermarked " id="cert">
            <div
              style=" width:1200px;font-family:Helvetica;  ">
              <div style="width: 150px; float: left;">
                <img src="../../assets/images/mdc_logo.png" height="130" alt="LOGO" />
              </div>
              <div style="width: 870px; float: left; text-align: center;">
                <b class="text-blue font-20">MEDICAL AND DENTAL COUNCIL (GHANA)</b> <br>
                <em class="font-13 text-danger"><b>"Guiding the professions, protecting the Public"</b> </em> 
                <br>
                <b class="font-20">
                  Certificate of {{register_type}} Registration as a
                </b>
                <br>
                <b class="text-danger weird-font">
                  {{type}}
                </b>
                <br>
                <span style="font-size: 13px; padding-bottom: 20px;">
                  Section 35 of the HPRB Act, 2013 (Act 857)
                </span>
              </div>
              <div style="width: 100px; float: left;">
                  <img src="{{picture}}" height="130" alt="practitioner picture. Invalid if empty" />       </div>
            </div>
            <div style="width:1200; font-weight: bold; margin-bottom: 30px; padding-top: 30px; min-height: 116px;">
              <div style=" float: left; width: 200px; padding-top: 10px;">
                This is to certify that Dr 
              </div>
              <div style=" width: 625px; float: left; border-bottom: 3px dotted black;  padding-top: 10px;">
                <span style="padding-left: 5px;">{{name}}  </span> 
              </div>
              <div style=" float: left; width: 100px; padding-top: 10px;">
                  Reg. Num.
                </div>
              <div style=" width: 148px; float: left; border-bottom: 3px dotted black;  padding-top: 10px;">
                  <span class="pull-right">{{registration_number}}</span>
                </div>
          
            </div>
            <br>
          
            <div style="width:1200px; font-weight: bold; padding-top: 30px; padding-bottom: 30px;">
              <div style=" float: left; margin-left: 10px;">
                has paid up for retention of name in the Register for the year
              </div>
              <!-- <div style=" width: 60px; float: left; border-bottom: 3px dotted black;">
                <span style="padding-left: 5px;">{{amount}} </span>
              </div> -->
              <!-- <div style="margin-left: 5px; float: left;">
                being fee for retention of name in the Register for the year
              </div> -->
              <div style=" float: left; border-bottom: 3px dotted black;">
                <span style="padding-left: 5px;"> {{year}}</span>
              </div>
            </div>
          
            <!-- <div style="width:820px; font-weight: bold; padding-top: 30px; padding-bottom: 30px;">
              <div style="width: 600px; float: left; text-align: center;">Qualifications</div>
              <div style="width: 120px; float: left; text-align: center;">Year Granted</div>
            </div> -->
            <div 
            style="width:1100px; font-weight: bold; padding-top: 30px; padding-bottom: 30px;">
          
            <table class="table">
              <thead>
                <tr>
                  <td></td>
                  <th>Qualifications</th>
                  <th>Year Granted</th>
                </tr>
              </thead>
              <tbody>
                <tr *ngFor="let q of qualifications; let i = index">
                  <td>{{i+1}} </td>
                  <td>
                    <div style="  border-bottom: 3px dotted black;">
                      {{q.qualification}} ({{q.institution}})
                    </div>
                  </td>
                  <td>
                    <div style="  border-bottom: 3px dotted black;">
                        {{dbService.getYear(q.end_date)}}
                    </div>
                  </td>
                </tr>
              </tbody>
          
            </table>
          </div>
          
          <div [innerHTML]="specialist_data"></div>
          
            <!-- <div *ngFor="let q of qualifications; let i = index" style="width:820px; font-weight: bold; padding-top: 10px; padding-bottom: 20px;">
              <div style="width: 50px; padding-right: 10px; float: left;">
                  <span>{{i +1}}. &nbsp; </span> 
              </div>  
              <div style="width: 600px; margin-right: 100px; float: left; border-bottom: 3px dotted black;">
                  {{q.qualification}} ({{q.institution}})
                </div>
                <div style="width: 50px; float: left; border-bottom: 3px dotted black;">
                    {{dbService.getYear(q.end_date)}}
                </div>
              </div> -->
          
              <div
              style=" width:120px; padding-top: 40px;">
              <div style="width: 400px; float: left;">
                <qr-code [value]="qr_text"></qr-code>
          
                 <img src="{{qr_code}}" height="150" alt="scan to authenticate. Invalid if empty" /> 
               
                <br>
                <span class="font-13">Scan with a qr scanner app to check for validity</span>
              
              </div>
              
          
            </div>
            <div style="padding-top: 103px; float: left; text-align: right;">
                <h4 >VALID TILL : {{expiry}} </h4>
                
              </div>
           
          
          
          </div>',
            "type" => "string",
            "control_type" => "textarea",
        ],
        [
            "class" => "Practitioners",
            "key" => "relicensure_certificate_physician_assistants",
            "value" => '<div class="good_standing_cert watermarked " id="cert">
            <div
              style=" width:1200px;font-family:Helvetica;  ">
              <div style="width: 150px; float: left;">
                <img src="../../assets/images/mdc_logo.png" height="130" alt="LOGO" />
              </div>
              <div style="width: 870px; float: left; text-align: center;">
                <b class="text-blue font-20">MEDICAL AND DENTAL COUNCIL (GHANA)</b> <br>
                <em class="font-13 text-danger"><b>"Guiding the professions, protecting the Public"</b> </em> 
                <br>
                <b class="font-20">
                  Certificate of {{register_type}} Registration as a
                </b>
                <br>
                <b class="text-danger weird-font">
                  {{type}}
                </b>
                <br>
                <span style="font-size: 13px; padding-bottom: 20px;">
                  Section 35 of the HPRB Act, 2013 (Act 857)
                </span>
              </div>
              <div style="width: 100px; float: left;">
                  <img src="{{picture}}" height="130" alt="practitioner picture. Invalid if empty" />       </div>
            </div>
            <div style="width:1200; font-weight: bold; margin-bottom: 30px; padding-top: 30px; min-height: 116px;">
              <div style=" float: left; width: 200px; padding-top: 10px;">
                This is to certify that Dr 
              </div>
              <div style=" width: 625px; float: left; border-bottom: 3px dotted black;  padding-top: 10px;">
                <span style="padding-left: 5px;">{{name}}  </span> 
              </div>
              <div style=" float: left; width: 100px; padding-top: 10px;">
                  Reg. Num.
                </div>
              <div style=" width: 148px; float: left; border-bottom: 3px dotted black;  padding-top: 10px;">
                  <span class="pull-right">{{registration_number}}</span>
                </div>
          
            </div>
            <br>
          
            <div style="width:1200px; font-weight: bold; padding-top: 30px; padding-bottom: 30px;">
              <div style=" float: left; margin-left: 10px;">
                has paid up for retention of name in the Register for the year
              </div>
              <!-- <div style=" width: 60px; float: left; border-bottom: 3px dotted black;">
                <span style="padding-left: 5px;">{{amount}} </span>
              </div> -->
              <!-- <div style="margin-left: 5px; float: left;">
                being fee for retention of name in the Register for the year
              </div> -->
              <div style=" float: left; border-bottom: 3px dotted black;">
                <span style="padding-left: 5px;"> {{year}}</span>
              </div>
            </div>
          
            <!-- <div style="width:820px; font-weight: bold; padding-top: 30px; padding-bottom: 30px;">
              <div style="width: 600px; float: left; text-align: center;">Qualifications</div>
              <div style="width: 120px; float: left; text-align: center;">Year Granted</div>
            </div> -->
            <div 
            style="width:1100px; font-weight: bold; padding-top: 30px; padding-bottom: 30px;">
          
            <table class="table">
              <thead>
                <tr>
                  <td></td>
                  <th>Qualifications</th>
                  <th>Year Granted</th>
                </tr>
              </thead>
              <tbody>
                <tr *ngFor="let q of qualifications; let i = index">
                  <td>{{i+1}} </td>
                  <td>
                    <div style="  border-bottom: 3px dotted black;">
                      {{q.qualification}} ({{q.institution}})
                    </div>
                  </td>
                  <td>
                    <div style="  border-bottom: 3px dotted black;">
                        {{dbService.getYear(q.end_date)}}
                    </div>
                  </td>
                </tr>
              </tbody>
          
            </table>
          </div>
          
          <div [innerHTML]="specialist_data"></div>
          
            <!-- <div *ngFor="let q of qualifications; let i = index" style="width:820px; font-weight: bold; padding-top: 10px; padding-bottom: 20px;">
              <div style="width: 50px; padding-right: 10px; float: left;">
                  <span>{{i +1}}. &nbsp; </span> 
              </div>  
              <div style="width: 600px; margin-right: 100px; float: left; border-bottom: 3px dotted black;">
                  {{q.qualification}} ({{q.institution}})
                </div>
                <div style="width: 50px; float: left; border-bottom: 3px dotted black;">
                    {{dbService.getYear(q.end_date)}}
                </div>
              </div> -->
          
              <div
              style=" width:120px; padding-top: 40px;">
              <div style="width: 400px; float: left;">
                <qr-code [value]="qr_text"></qr-code>
          
                 <img src="{{qr_code}}" height="150" alt="scan to authenticate. Invalid if empty" /> 
               
                <br>
                <span class="font-13">Scan with a qr scanner app to check for validity</span>
              
              </div>
              
          
            </div>
            <div style="padding-top: 103px; float: left; text-align: right;">
                <h4 >VALID TILL : {{expiry}} </h4>
                
              </div>
           
          
          
          </div>',
            "type" => "string",
            "control_type" => "textarea",
        ]],
    );
    }

    public function down()
    {
        //
    }
}
