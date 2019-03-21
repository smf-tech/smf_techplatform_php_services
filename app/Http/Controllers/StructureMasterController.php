<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\DB;
use App\StructureMaster;
use App\District;
use App\Taluka;
use App\Village;
use Carbon\Carbon;
use App\StructureTracking;

class StructureMasterController extends Controller
{
    use Helpers;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function get()
    {
        try {
            $database = $this->connectTenantDatabase($this->request);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }
			$userLocation = $this->request->user()->location;
			$structure = StructureMaster::where('structure_code', '!=', null);

			foreach ($userLocation as $level => $location) {
				$structure->whereIn(strtolower($level) . '_id', $location);
			}
			$structure->with('state', 'district', 'taluka', 'village');
            return response()->json([
                'status' => 'success',
                'data' => $structure->get(['structure_code', 'state_id', 'district_id', 'taluka_id', 'village_id']),
                'message' => 'List of Structure codes.'
            ],200);
        } catch(\Exception $exception) {
            return response()->json(
                    [
                        'status' => 'error',
                        'data' => '',
                        'message' => $exception->getMessage()
                    ],
                    404
                );
            }
    }

    public function structureCreate($form_id){
        try {
            $database = $this->connectTenantDatabase($this->request);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }

            $userId = $this->request->user()->id;
            $department_abbr = array('water_resources_department'=>'WRD','forest'=>'FST',
                                     'agriculture'=>'AGR','minor_irrigation(ZP)'=>'MIZP',
                                     'soil_and_water_conservation'=>'SWC',
                                     'irrigation_department'=>'IRG'
                                    );
            $struct_abbr = array('cct'=>'CCT','deep_cct'=>'DCCT','nala'=>'NALA',
                                 'talav'=>'TLAV','dam'=>'DAM','canal'=>'CANL',
                                 'mnb'=>'MNB','cnb'=>'CNB','farm_pond'=>'FRMP',
                                 'river'=>'RIVR','mi_tank'=>'MITK',
                                 'percolation_tank'=>'PCTK',
                                 'kt_ware'=>'KTWR','graded_contour_bunding'=>'GCB',
                                 'contour_bunding-CB'=>'CB',
                                 'inlet_outlet_farm_pond_IO'=>'FP[I]',
                                 'trench_cum_mount_or_cow_protection_trench-TCM'=>'TCM',
                                 'cpt'=>'CPT',
                                 'earthen_gully_plug_EGP'=>'EGP',
                                 'nala_rundikaran_kholikaran-NRK'=>'NRK',
                                 'compartment_bunding-CB'=>'CMPB',
                                 'village_cleaning-VC'=>'VC',
                                 'dhalicha_bandh'=>'GCB','vantale'=>'FSTP');
            
            $data = $this->request->all();
            $district = District::find($this->request->input('district'));
            $taluka = Taluka::find($this->request->input('taluka'));
            $village = Village::find($this->request->input('village'));
            $department_code = $department_abbr[$this->request->input('structure_owner_department')];
            $structuretype_code = $struct_abbr[$this->request->input('type')];
          
            $structures =StructureMaster::where('structure_code','LIKE',$district->abbr.'/'.$taluka->abbr.'/'.$village->name.'/'.$department_code.'/'.$structuretype_code.'%')->orderby('createdDateTime','DESC')->get()->first();
            //var_dump($structures->structure_code);exit;
            if($structures){
                $numberoffset = strlen($district->abbr.'/'.$taluka->abbr.'/'.$village->name.'/'.$department_code.'/'.$structuretype_code);
                $queueValue = (int)substr($structures->structure_code ,$numberoffset)+1;
            }else{
                $queueValue = 1;  
            }
            $structure_code = $district->abbr.'/'.$taluka->abbr.'/'.$village->name.'/'.$department_code.'/'.$structuretype_code.$queueValue;



            $structure_master = new StructureMaster;

            $primaryKeys = \App\Survey::find($form_id)->form_keys;
			$condition = ['userName' => $userId];
			$associatedFields = array_map('strtolower', $this->getLevels()->toArray());
			foreach ($data as $field => $value) {
				
				if (in_array($field, $associatedFields)) {
					if (in_array($field, $primaryKeys) && !empty($value)) {
						$field .= '_id';
						$condition[$field] = $value;
					} else {
						$field .= '_id';
					}
				}
				if (in_array($field, $primaryKeys) && !empty($value)) {
					$condition[$field] = $value;
				}
				$structure_master->$field = $value;
            }

            $existingStructure = StructureMaster::where($condition)->first();
			if (isset($existingStructure)) {
				return response()->json(
						[
						'status' => 'error',
						'data' => '',
						'message' => 'Structure already exists. Please change the parameters.'
					],
					400
				);
			}

            $structure_master->userName = $this->request->user()->id;
            $structure_master->structure_code = $structure_code;
            $structure_master->isDeleted = false;
            $structure_master->form_id = $form_id;
           
            $structure_master->save();

            $result = [
                '_id' => [
                    '$oid' => $structure_master->id
                ],
                'form_title' => $this->generateFormTitle($form_id,$structure_master->id,'structure_masters'),
				'createdDateTime' => $structure_master->createdDateTime,
				'updatedDateTime' => $structure_master->updatedDateTime
            ]; 
            return response()->json([
                'status' => 'success',
                'data' => $result,
                'message' => 'Created Record in Structure Master'
            ],200);
        } catch(\Exception $exception) {
            return response()->json(
                    [
                        'status' => 'error',
                        'data' => '',
                        'message' => $exception->getMessage()
                    ],
                    404
                );
            }     
    }

	public function getStructures($formId)
	{
		try {
			$database = $this->connectTenantDatabase($this->request);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}

			$userName = $this->request->user()->id;
			$limit = (int)$this->request->input('limit') ?:50;
			$offset = $this->request->input('offset') ?:0;
			$order = $this->request->input('order') ?:'desc';
			$field = $this->request->input('field') ?:'createdDateTime';
			$page = $this->request->input('page') ?:1;
			$startDate = $this->request->filled('start_date') ? $this->request->start_date : Carbon::now()->subMonth()->getTimestamp();
			$endDate = $this->request->filled('end_date') ? $this->request->end_date : Carbon::now()->getTimestamp();

			$structures = StructureMaster::where('userName', $userName)
					->where('form_id', $formId)
                    ->whereBetween('createdDateTime', [$startDate, $endDate])
                    ->where('isDeleted','!=',true)
					->with('district', 'taluka', 'village')
					->orderBy($field, $order)
					->paginate($limit);

			if ($structures->count() === 0) {
				return response()->json(['status' => 'success', 'metadata' => [],'values' => [], 'message' => ''],200);
			}
			$createdDateTime = $structures->last()['createdDateTime'];
			$updatedDateTime = $structures->first()['updatedDateTime'];
			$resonseCount = $structures->count();

			$result = [
				'form' => [
					'form_id' => $formId,
					'userName' => $structures->first()['userName'],
					'createdDateTime' => $createdDateTime,
					'updatedDateTime' => $updatedDateTime,
					'submit_count' => $resonseCount
				]
			];

			$values = [];
			foreach ($structures as &$structure) {
				foreach (array_map('strtolower', $this->getLevels()->toArray()) as $singleJurisdiction) {
					if (isset($structure[$singleJurisdiction])) {
						unset($structure[$singleJurisdiction]);
						$structure[$singleJurisdiction] = $structure[$singleJurisdiction . '_id'];
						unset($structure[$singleJurisdiction . '_id']);
					}
				}
				$structure['form_title'] = $this->generateFormTitle($formId, $structure['_id'], 'structure_masters');
				$values[] = \Illuminate\Support\Arr::except($structure, ['form_id', 'userName', 'createdDateTime']);
			}

			$result['Current page'] = 'Page ' . $structures->currentPage() . ' of ' . $structures->lastPage();
			$result['Total number of records'] = $structures->total();

			return response()->json([
				'status' => 'success',
				'metadata' => [$result],
				'values' => $values,
				'message '=> ''
            ],200);
		} catch(\Exception $exception) {
			return response()->json(
				[
					'status' => 'error',
					'data' => '',
					'message' => $exception->getMessage()
				],
				404
			);
		}
    }
    
    public function deleteStructure($recordId)
	{
        try {

        $database = $this->connectTenantDatabase($this->request);
			if ($database === null) {
				return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
			}

        $structure = StructureMaster::find($recordId);

        if(empty($structure)) {
            return response()->json(
				[
					'status' => 'error',
					'data' => '',
					'message' => "Record does not exist"
				],
				404
			);
        }

        if((isset($structure->userName) && $this->request->user()->id !== $structure->userName) || (isset($structure->created_by) && $this->request->user()->id !== $structure->created_by)) {
            return response()->json(
                [
                    'status' => 'error',
                    'data' => '',
                    'message' => "Record cannot be deleted as you are not the creator of the record"
                ],
                403
                );
        }
        $structures = StructureTracking::where('structure_code',$structure->structure_code)
                                        ->where('isDeleted','!=',true)
                                        ->update(['isDeleted' => true]);

        $structure->isDeleted = true;
        $structure->save();

        return response()->json(
            [
                'status' => 'success',
                'data' => '',
                'message' => "Record deleted successfully"
            ],
            200
        );

        } catch(\Exception $exception) {
            return response()->json(
				[
					'status' => 'error',
					'data' => '',
					'message' => $exception->getMessage()
				],
				404
            );
        }
        
    }
    
}
