<?php

namespace SalamWaddah\SmsGlobal;

class SmsGlobalMessage
{
    private string $content;

    public function content(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
