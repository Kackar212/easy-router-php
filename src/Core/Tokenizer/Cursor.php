<?php

namespace EasyRouter\Core\Tokenizer;

class Cursor {
    private $state;
    private int $end;
    private string $content;

    public function __construct(Cursor $cursor = null) {
        if ($cursor !== null) {
            $this->init($cursor->content);
            $this->state = clone $cursor->state;
        }
    }

    public function init(string $content) {
        $this->end = strlen($content);
        $this->content = $content;
        $this->state = (object) [
            "position" => 0,
        ];
    }

    public function getPosition() {
        return $this->state->position;
    }

    public function tryAdvancePosition(int $char) {
        if ($this->getChar() !== $char) {
            return false;
        }

        $this->state->position += 1;

        return true;
    }

    public function advance() {
        if ($this->state->position > $this->end) {
            return false;
        }

        $this->state->position += 1;

        return true;
    }

    public function getChar() {
        if (!isset($this->content[$this->state->position])) {
            return -1;
        }

        return mb_ord($this->content[$this->state->position]);
    }

    // consumeStatic -> user-
    public function advanceUntil(callable $callback) {
        while (call_user_func($callback, $this->getChar()) && $this->getChar() !== -1) {
            $this->state->position += 1;
        }
    }

    public function copy() {
        return new Cursor($this);
    }

    public function getRange(Cursor $startCursor, int $moveBy = 0) {
        return substr($this->content, $startCursor->state->position, $this->state->position - $startCursor->state->position + $moveBy);
    }

    public function isEnd() {
        return $this->state->position >= $this->end;
    }
}