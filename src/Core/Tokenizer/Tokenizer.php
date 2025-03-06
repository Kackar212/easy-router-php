<?php

namespace EasyRouter\Core\Tokenizer;

class Tokenizer
{
    private Cursor $cursor;
    private ?object $token = null;
    private array $tokens = [];
    private int $count = 0;
    public function __construct(Cursor $cursor)
    {
        $this->cursor = $cursor;
    }

    public function tokenize(string $content): array
    {
        $this->tokens = [];
        $this->cursor->init($content);

        while (!$this->cursor->isEnd()) {
            if ($this->cursor->tryAdvancePosition(Chars::CURLY_LEFT)) {
                $this->consumeParameter();

                continue;
            }

            $this->consumeStaticData();
        }

        return $this->tokens;
    }

    public function consumeParameter()
    {
        $isOptional = $this->cursor->tryAdvancePosition(Chars::QUESTION_MARK);
        $cursorStart = $this->cursor->copy();

        $this->cursor->advanceUntil(function (int $char) use ($isOptional, $cursorStart) {
            if ($this->cursor->tryAdvancePosition(Chars::LESS_THAN)) {
                $regexpStart = $this->cursor->copy();
                $regexp = $this->consumeRegexp($regexpStart);
                $parameter = $regexpStart->getRange($cursorStart, -1);

                $this->token = (object) ["value" => $parameter, "type" => Token::PARAMETER, "isOptional" => $isOptional, "regexp" => $regexp];

                $this->cursor->advance();

                return false;
            }

            return $char !== Chars::CURLY_RIGHT;
        });


        $parameter = $this->cursor->getRange($cursorStart);

        $this->cursor->advance();

        $this->tokens[] = $this->token ?? (object) ["value" => $parameter, "type" => Token::PARAMETER, "isOptional" => $isOptional, "regexp" => ""];
        $this->token = null;
    }

    public function consumeRegexp(Cursor $cursorStart)
    {
        $this->cursor->advance();

        $this->count = 0;
        $this->cursor->advanceUntil(function (int $char) {
            if ($char === Chars::LESS_THAN) {
                $this->count++;
            }

            $result = $char !== Chars::GREATER_THAN || $this->count !== 0;

            if ($char === Chars::GREATER_THAN && $this->count > 0) {
                $this->count--;
            }

            return $result;
        });

        return $this->cursor->getRange($cursorStart);
    }

    public function consumeStaticData()
    {
        $cursorStart = $this->cursor->copy();

        $this->cursor->advanceUntil(function (int $char) {
            return $char !== Chars::CURLY_LEFT;
        });

        $value = $this->cursor->getRange($cursorStart);

        $this->tokens[] = (object) ["value" => $value, "type" => Token::STATIC];
    }
}