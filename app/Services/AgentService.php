<?php

namespace App\Services;

use Jenssegers\Agent\Agent;

class AgentService
{
    protected $agent;

    public function __construct() {
        $this->agent = new Agent();
    }

    public function getAgent($request) {
        return [
            'browser' => $this->agent->browser(),
            'browser_version' => $this->agent->version($this->agent->browser()),
            'platform' => $this->agent->platform(),
            'platform_version' => $this->agent->version($this->agent->platform()),
            'device' => $this->agent->device(),
            'is_mobile' => $this->agent->isMobile(),
            'is_desktop' => $this->agent->isDesktop(),
            'user_agent' => $this->agent->getUserAgent()
        ];
    }
}
