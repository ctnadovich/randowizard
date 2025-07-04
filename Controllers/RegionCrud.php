<?php

//    Randonneuring.org Website Software
//    Copyright (C) 2023 Chris Nadovich
//
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU Affero General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU Affero General Public License for more details.
//
//    https://randonneuring.org/LICENSE.txt
//
//    You should have received a copy of the GNU Affero General Public License
//    along with this program.  If not, see <https://www.gnu.org/licenses/>.

namespace App\Controllers;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Libraries\GroceryCrud;

class RegionCrud extends BaseController
{

    protected $helpers = ['form'];

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
    }


    public function region()
    {

        $this->login_check();
        $member_id = $this->getMemberID();
        $rbaModel = model('Rba');
        $authorized_regions = $rbaModel->getAuthorizedRegions($member_id);
        if (empty($authorized_regions) && false == $this->isSuperuser())
            $this->die_info('Access Denied', "No regions authorized for this user.");

        $crud = new GroceryCrud();
        $crud->setTable('region');
        // $crud->setRelation('event_timezone_id', 'tz', '{name}');
        $crud->setRelation('state_id', 'state', '{fullname}');
        $crud->setRelation('country_id', 'country', '{fullname}');

        $crud->unsetEditFields(['epp_secret', 'id', 'state_code', 'state_id', 'country_id', 'region_name']);

        if (false == $this->isSuperuser()) {
            $crud->unsetAdd();
            $crud->unsetDelete();

            $where_list = array_map(fn($r) => "region.id = $r", $authorized_regions);
            $where_clause = '(' . implode(' OR ', $where_list) . ')';
            $crud->where($where_clause);
        }
        $crud->setRead();
        $crud->setSubject('Region', 'Regions');
        $crud->columns(['state_id', 'region_name', 'club_name', 'event_timezone_name', 'administrator', 'events']);

 // Define a list of timezones
$timezones = \DateTimeZone::listIdentifiers();

$tz_regions = [];
    foreach (\DateTimeZone::listIdentifiers() as $timezone) {
        $parts = explode('/', $timezone, 2);
        $tz_region = $parts[0];
        $name = isset($parts[1]) ? str_replace('_', ' ', $parts[1]) : $tz_region;

        if (!isset($tz_regions[$tz_region])) {
            $tz_regions[$tz_region] = [];
        }
        $tz_regions[$tz_region][$timezone] = $name;
    }



$timezone_options = '';

/* foreach ($timezones as $tz) {
    $timezone_options .= "<option value='{$tz}'>{$tz}</option>";
}
 */

foreach ($tz_regions as $tz_region => $timezones) {
    $timezone_options.= "<optgroup label=\"$tz_region\">";
    foreach ($timezones as $tz => $name) {
        $timezone_options.= "<option value=\"$tz\">$name</option>";
    }
    $timezone_options.= "</optgroup>";
}


// Callback functions to render the dropdown in the add/edit forms
$crud->callbackAddField('event_timezone_name', function() use ($timezone_options) {
    return "<select name='event_timezone_name'>{$timezone_options}</select>";
});

$crud->callbackEditField('event_timezone_name', function($value, $primary_key) use ($timezone_options) {
    return "<select name='event_timezone_name'><option value='{$value}' selected>{$value}</option>{$timezone_options}</select>";
});


        $crud->callbackColumn('events', array($this, '_event_info'));
        // $crud->callbackColumn('event_timezone_name', array($this, '_timezone_selector'));

        // Comma separated list of authorized users to display in datagrid
        $crud->callbackColumn('administrator', function ($value, $row) {
            $rbaModel = model('Rba');
            $region_id = $row->id;
            $authorized_users = $rbaModel->getAuthorizedUserObjects($region_id);
            $ar_list = [];
            foreach ($authorized_users as $u) {
                extract($u);
                $ar_list[] = "$first $last";
            }
            return implode(', ', $ar_list);
        });

        // $crud->displayAs('id', 'RBA');
        $crud->displayAs('event_timezone_name', 'Time Zone');
        $crud->displayAs('state_id', "State");
        $crud->fieldType('website_url', 'url');
        // $crud->displayAs('id', 'ACP Code');

        // $crud->fieldType('epp_secret', 'password');
        // $crud->displayAs('epp_secret', 'EPP Secret');
        $crud->unsetReadFields(['epp_secret']);
        // $crud->callbackEditField('epp_secret', [$this, 'clear_epp_secret_field']);
        // $crud->callbackBeforeUpdate([$this, 'update_callback']);

        $crud->setTexteditor(['region_description']);


        $output = $crud->render();

        $this->viewData = array_merge((array)$output, $this->viewData);

        return $this->load_view(['echo_output']);
    }

    public function _timezone_selector($value, $row)
    {
        $text = "<select name='timezone'>";
        foreach (\DateTimeZone::listIdentifiers() as $timezone) {
            $selected = $timezone==$value?'selected':'';
            $text .= "<option $selected value=\"$timezone\">$timezone</option>";
        }
        $text .= '</select>';
    }

    public function _event_info($value, $row)
    {
        $region_id = $row->id;
        $wizard_url = site_url("regional_events/$region_id");

        return <<<EOT
        <div class='w3-container w3-center' >
<A HREF='$wizard_url' class='w3-button w3-blue'><i class='w3-large fa-solid fa-info-circle'></i></A>
</div>
EOT;
    }

    protected $not_a_password = "not_a_password";

    public function clear_epp_secret_field($fieldValue, $primaryKeyValue, $rowData)
    {
        return "<input type='password' name='epp_secret' value='{$this->not_a_password}' />";
    }

    public function update_callback($stateParameters)
    {
        $password = $stateParameters->data['epp_secret'];
        if (!empty($password) && $password != $this->not_a_password) {
            $stateParameters->data['epp_secret'] = $password;
        } else {
            unset($stateParameters->data['epp_secret']);
        }

        return $stateParameters;
    }
}
