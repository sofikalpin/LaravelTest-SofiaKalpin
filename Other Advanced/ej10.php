<?php

$reports = Report::with([
        'department:id,name',
        'author:id,name',
        'comments:id,report_id,author_id,content',
        'comments.author:id,name',
        'attachments:id,report_id,filename,filesize'
    ])
    ->withCount('comments')
    ->where('status', 'published')
    ->orderBy('created_at', 'desc')
    ->get();

foreach ($reports as $report) {
    echo $report->title . ' by ' . $report->author->name . ' (' . $report->department->name . ')';
    echo ' Comments: ' . $report->comments_count; // Uses withCount() instead of fetching all comments

    foreach ($report->comments as $comment) {
        echo $comment->author->name . ': ' . $comment->content;
    }

    foreach ($report->attachments as $attachment) {
        echo $attachment->filename . ' (' . $attachment->filesize . ' bytes)';
    }
}
