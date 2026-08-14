<?php
/**
 * Module registry for the School Event section.
 * Each entry drives the generic list/create/edit/delete controllers in
 * /modules/list.php, /modules/form.php, /modules/delete.php
 *
 * field types supported by includes/form_fields.php:
 *   text | textarea | number | date | time | datetime | select | event_select
 */

return [

    'events' => [
        'label'        => 'Event Planning & Creation',
        'table'        => 'events',
        'primary_key'  => 'event_id',
        'order_by'     => 'start_date DESC',
        'list_columns' => ['event_name' => 'Event Name', 'event_type' => 'Type',
                            'start_date' => 'Start', 'end_date' => 'End',
                            'location' => 'Location', 'status' => 'Status'],
        'fields' => [
            'event_name'  => ['label' => 'Event Name', 'type' => 'text', 'required' => true],
            'description' => ['label' => 'Description', 'type' => 'textarea'],
            'event_type'  => ['label' => 'Event Type', 'type' => 'select',
                'options' => ['Orientation','Seminar','Cultural','Recognition','Organization-Led','Sports','Other']],
            'start_date'  => ['label' => 'Start Date', 'type' => 'date', 'required' => true],
            'end_date'    => ['label' => 'End Date', 'type' => 'date', 'required' => true],
            'location'    => ['label' => 'Location', 'type' => 'text'],
            'organizer'   => ['label' => 'Organizer', 'type' => 'text'],
            'status'      => ['label' => 'Status', 'type' => 'select',
                'options' => ['Planned','Ongoing','Completed','Cancelled']],
        ],
    ],

    'registrations' => [
        'label'        => 'Participant Registration & Management',
        'table'        => 'registrations',
        'primary_key'  => 'registration_id',
        'order_by'     => 'registered_at DESC',
        'list_columns' => ['participant_name' => 'Participant', 'student_id' => 'Student ID',
                            'course_program' => 'Course/Program', 'year_level' => 'Year Level',
                            'status' => 'Status'],
        'fields' => [
            'event_id'         => ['label' => 'Event', 'type' => 'event_select', 'required' => true],
            'participant_name' => ['label' => 'Full Name', 'type' => 'text', 'required' => true],
            'student_id'       => ['label' => 'Student ID', 'type' => 'text'],
            'course_program'   => ['label' => 'Course / Program', 'type' => 'text'],
            'year_level'       => ['label' => 'Year Level', 'type' => 'select',
                'options' => ['1st Year','2nd Year','3rd Year','4th Year']],
            'status'           => ['label' => 'Status', 'type' => 'select',
                'options' => ['Registered','Attended','Cancelled']],
        ],
    ],

    'venues' => [
        'label'        => 'Venue & Resource Scheduling',
        'table'        => 'venue_bookings',
        'primary_key'  => 'booking_id',
        'order_by'     => 'booking_date DESC, start_time',
        'list_columns' => ['venue_name' => 'Venue', 'resource_name' => 'Resources',
                            'booking_date' => 'Date', 'start_time' => 'Start',
                            'end_time' => 'End', 'status' => 'Status'],
        'fields' => [
            'event_id'      => ['label' => 'Event', 'type' => 'event_select', 'required' => true],
            'venue_name'    => ['label' => 'Venue', 'type' => 'text', 'required' => true],
            'resource_name' => ['label' => 'Resources', 'type' => 'text'],
            'booking_date'  => ['label' => 'Date', 'type' => 'date', 'required' => true],
            'start_time'    => ['label' => 'Start Time', 'type' => 'time', 'required' => true],
            'end_time'      => ['label' => 'End Time', 'type' => 'time', 'required' => true],
            'status'        => ['label' => 'Status', 'type' => 'select',
                'options' => ['Reserved','Confirmed','Conflict','Cancelled']],
        ],
        'conflict_check' => true, // enables overlap validation in form.php
    ],

    'invitations' => [
        'label'        => 'Invitation & Communication System',
        'table'        => 'invitations',
        'primary_key'  => 'invitation_id',
        'order_by'     => 'created_at DESC',
        'list_columns' => ['target_group' => 'Target Group', 'channel' => 'Channel',
                            'status' => 'Status', 'sent_at' => 'Sent At'],
        'fields' => [
            'event_id'     => ['label' => 'Event', 'type' => 'event_select', 'required' => true],
            'target_group' => ['label' => 'Target Group', 'type' => 'text', 'required' => true],
            'message'      => ['label' => 'Message', 'type' => 'textarea', 'required' => true],
            'channel'      => ['label' => 'Channel', 'type' => 'select',
                'options' => ['Email','SMS','In-App']],
            'status'       => ['label' => 'Status', 'type' => 'select',
                'options' => ['Draft','Sent','Failed']],
        ],
    ],

    'attendance' => [
        'label'        => 'Attendance Tracking & Verification',
        'table'        => 'attendance_records',
        'primary_key'  => 'attendance_id',
        'order_by'     => 'check_in_time DESC',
        'list_columns' => ['registration_id' => 'Registration ID', 'check_in_time' => 'Check-in Time',
                            'method' => 'Method', 'status' => 'Status'],
        'fields' => [
            'event_id'        => ['label' => 'Event', 'type' => 'event_select', 'required' => true],
            'registration_id' => ['label' => 'Registration ID', 'type' => 'number'],
            'check_in_time'   => ['label' => 'Check-in Time', 'type' => 'datetime'],
            'method'          => ['label' => 'Method', 'type' => 'select',
                'options' => ['QR Code','Manual','System']],
            'status'          => ['label' => 'Status', 'type' => 'select',
                'options' => ['Present','Absent','Late']],
        ],
    ],

    'budget' => [
        'label'        => 'Event Budget & Expense Tracking',
        'table'        => 'budget_entries',
        'primary_key'  => 'entry_id',
        'order_by'     => 'entry_date DESC',
        'list_columns' => ['category' => 'Category', 'entry_type' => 'Type',
                            'amount' => 'Amount', 'entry_date' => 'Date'],
        'fields' => [
            'event_id'    => ['label' => 'Event', 'type' => 'event_select', 'required' => true],
            'category'    => ['label' => 'Category', 'type' => 'text', 'required' => true],
            'description' => ['label' => 'Description', 'type' => 'text'],
            'entry_type'  => ['label' => 'Type', 'type' => 'select', 'options' => ['Budget','Expense']],
            'amount'      => ['label' => 'Amount (PHP)', 'type' => 'number', 'required' => true],
            'entry_date'  => ['label' => 'Date', 'type' => 'date', 'required' => true],
        ],
    ],

    'program' => [
        'label'        => 'Program Flow and Activity Monitoring',
        'table'        => 'program_segments',
        'primary_key'  => 'segment_id',
        'order_by'     => 'sort_order ASC',
        'list_columns' => ['segment_name' => 'Segment', 'scheduled_start' => 'Sched. Start',
                            'scheduled_end' => 'Sched. End', 'status' => 'Status'],
        'fields' => [
            'event_id'        => ['label' => 'Event', 'type' => 'event_select', 'required' => true],
            'segment_name'    => ['label' => 'Segment Name', 'type' => 'text', 'required' => true],
            'scheduled_start' => ['label' => 'Scheduled Start', 'type' => 'time', 'required' => true],
            'scheduled_end'   => ['label' => 'Scheduled End', 'type' => 'time', 'required' => true],
            'actual_start'    => ['label' => 'Actual Start', 'type' => 'time'],
            'actual_end'      => ['label' => 'Actual End', 'type' => 'time'],
            'status'          => ['label' => 'Status', 'type' => 'select',
                'options' => ['Pending','Ongoing','Completed','Delayed']],
            'sort_order'      => ['label' => 'Order', 'type' => 'number'],
        ],
    ],

    'media' => [
        'label'        => 'Multimedia & Documentation Portal',
        'table'        => 'media_files',
        'primary_key'  => 'media_id',
        'order_by'     => 'uploaded_at DESC',
        'list_columns' => ['file_name' => 'File Name', 'file_type' => 'Type',
                            'uploaded_by' => 'Uploaded By', 'uploaded_at' => 'Uploaded At'],
        'fields' => [
            'event_id'    => ['label' => 'Event', 'type' => 'event_select', 'required' => true],
            'file_name'   => ['label' => 'File Name', 'type' => 'text', 'required' => true],
            'file_type'   => ['label' => 'Type', 'type' => 'select',
                'options' => ['Photo','Video','Document','Report']],
            'file_path'   => ['label' => 'File Path / URL', 'type' => 'text', 'required' => true],
            'uploaded_by' => ['label' => 'Uploaded By', 'type' => 'text'],
        ],
    ],

    'feedback' => [
        'label'        => 'Feedback & Evaluation System',
        'table'        => 'feedback_entries',
        'primary_key'  => 'feedback_id',
        'order_by'     => 'submitted_at DESC',
        'list_columns' => ['participant_name' => 'Participant', 'rating' => 'Rating',
                            'sentiment' => 'Sentiment (AI)', 'submitted_at' => 'Submitted'],
        'fields' => [
            'event_id'         => ['label' => 'Event', 'type' => 'event_select', 'required' => true],
            'participant_name' => ['label' => 'Participant', 'type' => 'text', 'required' => true],
            'rating'           => ['label' => 'Rating (1-5)', 'type' => 'number'],
            'comments'         => ['label' => 'Comments', 'type' => 'textarea'],
            'sentiment'        => ['label' => 'Sentiment', 'type' => 'select',
                'options' => ['Positive','Neutral','Negative']],
        ],
    ],

];
