<?php
require_once "./assets/init.php";
$process_queue = $db->get(T_QUEUE, $pt->config->queue_count, "*");
if (
    count($process_queue) <= $pt->config->queue_count &&
    count($process_queue) > 0
) {
    foreach ($process_queue as $key => $value) {
        try {
            if ($value->processing == 0) {
                $video = $db->where("id", $value->video_id)->getOne(T_VIDEOS);
                $video_id = $video->id;
                $video_in_queue = $db
                    ->where("video_id", $video->id)
                    ->getOne(T_QUEUE);
                $db->where("video_id", $video->id);
                $db->update(T_QUEUE, [
                    "processing" => 1,
                ]);
                ob_end_clean();
                header("Content-Encoding: none");
                header("Connection: close");
                ignore_user_abort();
                ob_start();
                header("Content-Type: application/json");
                $size = ob_get_length();
                header("Content-Length: $size");
                ob_end_flush();
                flush();
                session_write_close();
                if (is_callable("fastcgi_finish_request")) {
                    fastcgi_finish_request();
                }
                $video_res = $video_in_queue->video_res;
                $ffmpeg_b = $pt->config->ffmpeg_binary_file;
                $filepath = explode(".", $video->video_location)[0];
                $time = time();
                $full_dir = str_replace("ajax", "/", __DIR__);

                $video_output_full_path_240 =
                    $full_dir . "/" . $filepath . "_240p_converted.mp4";
                $video_output_full_path_360 =
                    $full_dir . "/" . $filepath . "_360p_converted.mp4";
                $video_output_full_path_480 =
                    $full_dir . "/" . $filepath . "_480p_converted.mp4";
                $video_output_full_path_720 =
                    $full_dir . "/" . $filepath . "_720p_converted.mp4";
                $video_output_full_path_1080 =
                    $full_dir . "/" . $filepath . "_1080p_converted.mp4";
                $video_output_full_path_2048 =
                    $full_dir . "/" . $filepath . "_2048p_converted.mp4";
                $video_output_full_path_4096 =
                    $full_dir . "/" . $filepath . "_4096p_converted.mp4";

                $video_file_full_path =
                    $full_dir . "/" . $video->video_location;


                if ($pt->config->p240 == "on") {
                    $shell = shell_exec(
                        "$ffmpeg_b -y -i $video_file_full_path -vcodec libx264 -preset {$pt->config->convert_speed} -filter:v scale=426:-2 -crf 26 $video_output_full_path_240 2>&1"
                    );
                    $upload_s3 = PT_UploadToS3(
                        $filepath . "_240p_converted.mp4"
                    );
                    $db->where("id", $video->id);
                    $db->update(T_VIDEOS, [
                        "converted" => 1,
                        "240p" => 1,
                        "video_location" => $filepath . "_240p_converted.mp4",
                    ]);
                }
                if (
                    ($video_res >= 640 || $video_res == 0) &&
                    $pt->config->p360 == "on"
                ) {
                    $shell = shell_exec(
                        "$ffmpeg_b -y -i $video_file_full_path -vcodec libx264 -preset {$pt->config->convert_speed} -filter:v scale=640:-2 -crf 26 $video_output_full_path_360 2>&1"
                    );
                    $upload_s3 = PT_UploadToS3(
                        $filepath . "_360p_converted.mp4"
                    );
                    $db->where("id", $video->id);
                    $db->update(T_VIDEOS, [
                        "360p" => 1,
                        "converted" => 1,
                        "video_location" => $filepath . "_360p_converted.mp4",
                    ]);
                }

                if (
                    ($video_res >= 854 || $video_res == 0) &&
                    $pt->config->p480 == "on"
                ) {
                    $shell = shell_exec(
                        "$ffmpeg_b -y -i $video_file_full_path -vcodec libx264 -preset {$pt->config->convert_speed} -filter:v scale=854:-2 -crf 26 $video_output_full_path_480 2>&1"
                    );
                    $upload_s3 = PT_UploadToS3(
                        $filepath . "_480p_converted.mp4"
                    );
                    $db->where("id", $video->id);
                    $db->update(T_VIDEOS, [
                        "480p" => 1,
                        "converted" => 1,
                        "video_location" => $filepath . "_480p_converted.mp4",
                    ]);
                }

                if (
                    ($video_res >= 1280 || $video_res == 0) &&
                    $pt->config->p720 == "on"
                ) {
                    $shell = shell_exec(
                        "$ffmpeg_b -y -i $video_file_full_path -vcodec libx264 -preset {$pt->config->convert_speed} -filter:v scale=1280:-2 -crf 26 $video_output_full_path_720 2>&1"
                    );
                    $upload_s3 = PT_UploadToS3(
                        $filepath . "_720p_converted.mp4"
                    );
                    $db->where("id", $video->id);
                    $db->update(T_VIDEOS, [
                        "720p" => 1,
                        "converted" => 1,
                        "video_location" => $filepath . "_720p_converted.mp4",
                    ]);
                }

                if (
                    ($video_res >= 1920 || $video_res == 0) &&
                    $pt->config->p1080 == "on"
                ) {
                    $shell = shell_exec(
                        "$ffmpeg_b -y -i $video_file_full_path -vcodec libx264 -preset {$pt->config->convert_speed} -filter:v scale=1920:-2 -crf 26 $video_output_full_path_1080 2>&1"
                    );
                    $upload_s3 = PT_UploadToS3(
                        $filepath . "_1080p_converted.mp4"
                    );
                    $db->where("id", $video->id);
                    $db->update(T_VIDEOS, [
                        "1080p" => 1,
                        "converted" => 1,
                        "video_location" => $filepath . "_1080p_converted.mp4",
                    ]);
                }

                if ($video_res >= 2048 && $pt->config->p2048 == "on") {
                    $shell = shell_exec(
                        "$ffmpeg_b -y -i $video_file_full_path -vcodec libx264 -preset {$pt->config->convert_speed} -filter:v scale=2048:-2 -crf 26 $video_output_full_path_2048 2>&1"
                    );
                    $upload_s3 = PT_UploadToS3(
                        $filepath . "_2048p_converted.mp4"
                    );
                    $db->where("id", $video->id);
                    $db->update(T_VIDEOS, [
                        "2048p" => 1,
                        "converted" => 1,
                        "video_location" => $filepath . "_2048p_converted.mp4",
                    ]);
                }

                if ($video_res >= 3840 && $pt->config->p4096 == "on") {
                    $shell = shell_exec(
                        "$ffmpeg_b -y -i $video_file_full_path -vcodec libx264 -preset {$pt->config->convert_speed} -filter:v scale=3840:-2 -crf 26 $video_output_full_path_4096 2>&1"
                    );
                    $upload_s3 = PT_UploadToS3(
                        $filepath . "_4096p_converted.mp4"
                    );
                    $db->where("id", $video->id);
                    $db->update(T_VIDEOS, [
                        "4096p" => 1,
                        "converted" => 1,
                        "video_location" => $filepath . "_4096p_converted.mp4",
                    ]);
                }

                if (file_exists($video->video_location)) {
                    unlink($video->video_location);
                }
								$db->where("video_id", $video->id)->delete(T_QUEUE);
                pt_push_channel_notifiations($video_id);
            }
        } catch (Exception $e) {
            $db->where("video_id", $video->id)->delete(T_QUEUE);
            if (file_exists($video->video_location)) {
                unlink($video->video_location);
            }
        }
    }
}
