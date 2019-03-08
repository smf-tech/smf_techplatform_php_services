<?php

namespace App;

use Carbon\Carbon;

trait AuditFields {
	public static function boot()
	{
		parent::boot();

		static::creating(function($table) {
			$currentTimestamp = Carbon::now()->getTimestamp();
			$table->createdDateTime = $currentTimestamp;
			$table->updatedDateTime = $currentTimestamp;
		});

		static::updating(function($table) {
			$currentTimestamp = Carbon::now()->getTimestamp();
			$table->updatedDateTime = $currentTimestamp;
		});
	}
}
