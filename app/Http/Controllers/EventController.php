<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\EventAssociate;
use App\Event;
use App\EventParticipant;

class EventController extends Controller
{
	const DAILY = 'day';
	const WEEKLY = 'week';
	const MONTHLY = 'month';

	protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

	public function create()
	{
		try {
			$database = $this->connectTenantDatabase($this->request);
            if ($database === null) {
                return response()->json(['status' => 'error', 'data' => '', 'message' => 'User does not belong to any Organization.'], 403);
            }
			$data = $this->request->all();

			$recurrence = [];
			$startDateTimes = [];
			$endDateTimes = [];
			$durations = [];
			$userId = $this->request->user()->id;
			$eventParticipants = [];
			
			if (isset($data['recurrence']) && !empty($data['recurrence'])) {
				$recurrence = $data['recurrence'];
				$durations = $this->getDuration($data['recurrence'], $data['eventStartDateTime'], $data['eventEndDateTime'], $data['duration']);

				foreach($durations as $duration) {
					$existingEvent = Event::where('userName', $userId)
							->where('isDeleted', '!=', true)
							->where(function($query) use ($duration) {
								$query->whereBetween('eventStartDateTime', $duration)
										->orWhereBetween('eventEndDateTime', $duration);
					})->first();
					if ($existingEvent !== null) {
						return response()->json(
							[
								'status' => 'error',
								'data' => '',
								'message' => 'There is a conflict between existing and current Event timings.'
							],
							400
						);
					}
				}
				unset($data['recurrence'], $data['eventStartDateTime'], $data['eventEndDateTime']);
			} else {
				$existingEvent = Event::where('userName', $userId)
						->where('isDeleted', '!=', true)
						->where(function($query) use ($data) {
							$query->whereBetween('eventStartDateTime', [$data['eventStartDateTime'], $data['eventEndDateTime']])
									->orWhereBetween('eventEndDateTime', [$data['eventStartDateTime'], $data['eventEndDateTime']]);
				})->first();
				if ($existingEvent !== null) {
						return response()->json(
							[
								'status' => 'error',
								'data' => '',
								'message' => 'There is a conflict between existing and current Event timings.'
							],
							400
						);
					}
			}

			if (isset($data['participants']) && !empty($data['participants'])) {
				$eventParticipants = $data['participants'];
				unset($data['participants']);
			}

			$eventAssociate = EventAssociate::create([
				'eventName' => $data['eventName'],
				'userName' => $userId,
				'isDeleted' => false
			]);

			if (count($durations) > 0) {
				foreach ($durations as $duration) {
					$event = new Event;
					foreach ($data as $field => $value) {
						$event->{$field} = $value;
					}
					$event->eventStartDateTime = $duration[0];
					$event->eventEndDateTime = $duration[1];
					$event->parent = $eventAssociate->getIdAttribute();
					$event->userName = $userId;
					$event->save();
					$this->associateParticipants($eventParticipants, $event);
				}
			} else {
				$event = new Event;
				foreach ($data as $field => $value) {
					$event->{$field} = $value;
				}
				$event->parent = $eventAssociate->getIdAttribute();
				$event->userName = $userId;
				$event->save();
				$this->associateParticipants($eventParticipants, $event);
			}
			return response()->json(
					[
						'status' => 'success',
						'data' => [
							'_id' => $eventAssociate->getIdAttribute()
						],
						'message' => 'Events have been created successfully.'
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

	/**
	 * Get interval of Event/Task based on start date & end date and recurrence type
	 *
	 * @param array $recurrence
	 * @param int $startDateTime
	 * @param int $endDateTime
	 * @param int $duration
	 * @return array
	 */
	private function getDuration($recurrence, $startDateTime, $endDateTime, $duration)
	{
		$intervals = [];
		$startDateTime = Carbon::createFromTimestamp($startDateTime);
		$endDateTime = Carbon::createFromTimestamp($endDateTime);
		switch($recurrence['type']) {
			case self::DAILY:
				do {
					$endInterval = $startDateTime->copy()->addMinutes($duration);
					if ($endInterval->lte($endDateTime)) {
						$intervals[] = [
							$startDateTime->getTimestamp(),
							$endInterval->getTimestamp()
						];
					}
					$startDateTime->addDay();
				} while ($startDateTime->lte($endDateTime));
				break;
			case self::WEEKLY:
				Carbon::setWeekStartsAt(Carbon::SUNDAY);
				Carbon::setWeekEndsAt(Carbon::SATURDAY);
				$currentWeek = $startDateTime->copy()->startOfWeek();

				do {
					foreach ($recurrence['days'] as $day) {
						$weekDay = $currentWeek
								->copy()
								->next($day)
								->addHours($startDateTime->hour)
								->addMinutes($startDateTime->minute)
								->addSeconds($startDateTime->second);
						$endInterval = $weekDay->copy()->addMinutes($duration);

						if ($weekDay->gte($startDateTime) && $endInterval->lte($endDateTime)) {
							$intervals[] = [
								$weekDay->getTimestamp(),
								$endInterval->getTimestamp()
							];
						}
					}
					$currentWeek->addWeek();
				} while ($currentWeek->lte($endDateTime));
				break;
			case self::MONTHLY:
				$currentMonth = $startDateTime->copy()->startOfMonth();
				do {
					foreach ($recurrence['days'] as $day) {
						$selectedDay = $currentMonth
								->copy()
								->day($day)->addHours($startDateTime->hour)
								->addMinutes($startDateTime->minute)
								->addSeconds($startDateTime->second);
						$endInterval = $selectedDay->copy()->addMinutes($duration);
						if ($selectedDay->gte($startDateTime) && $endInterval->lte($endDateTime)) {
							$intervals[] = [
								$selectedDay->getTimestamp(),
								$endInterval->getTimestamp()
							];
						}
					}
					$currentMonth->addMonth();
				} while ($currentMonth->lte($endDateTime));
				break;
		}
		return $intervals;
	}

	private function associateParticipants($eventParticipants, $event)
	{
		if (count($eventParticipants) > 0) {
			foreach ($eventParticipants as $eventParticipant) {
				$participant = new EventParticipant;
				$participant->participantId = $eventParticipant['participantId'];
				$participant->attended = $eventParticipant['attended'];
				$participant->accepted = false;
				$participant->userName = $this->request->user()->id;
				$participant->isDeleted = false;
				$participant->event()->associate($event);
				$participant->save();
			}
		}
	}

}
