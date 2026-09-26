<?php
// routes/console.php

use Illuminate\Support\Facades\Schedule;

// PDF bag. 7: scheduler untuk reminder due date, overdue, warranty
Schedule::command('lendora:mark-overdue-borrowings')->hourly();
Schedule::command('lendora:expire-reservations')->hourly();
Schedule::command('lendora:send-due-reminders')->dailyAt('08:00');
Schedule::command('lendora:send-warranty-reminders')->dailyAt('07:30');
