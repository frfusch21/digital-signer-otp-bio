<?php

namespace App\Enums;

enum ThreatScenario: string
{
    case LegitimateUser = 'legitimate_user';
    case PhotoSpoofing = 'photo_spoofing';
    case VideoReplay = 'video_replay';
    case OtpChannelCompromise = 'otp_channel_compromise';
}
