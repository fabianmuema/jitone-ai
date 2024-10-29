<?php

namespace Jiten14\JitoneAi\Services;

use OpenAI\Laravel\Facades\OpenAI;

class OpenAIService
{
    public function generateContent(string $prompt, array $options = [])
    {
        $model = $options['model'] ?? config('jitone-ai.default_model');
        $maxTokens = $options['max_tokens'] ?? config('jitone-ai.default_max_tokens');
        $temperature = $options['temperature'] ?? config('jitone-ai.default_temperature');

        if ($model === 'gpt-3.5-turbo-instruct') {
            $completion = OpenAI::completions()->create([
                'model' => $model,
                'prompt' => $prompt . " use the following variables where necessary @first_name, @last_name, @email, @phone, @package_name, @expiry_at, @account_number, @paybill, @till_number, @password, @username",
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
            ]);

            return $completion['choices'][0]['text'];
        } else {
            $result = OpenAI::chat()->create([
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt . " consider the following instructions. Remove the subject and only remain
                         with the text itself additionally use the following client variables where necessary
                          @first_name, @last_name, @package_name, @expiry_at, @account_number, @paybill,
                           @till_number, @password, @username. Please note that these are not our
                            variables and should not be used to refer to our contact information.
                             Do not consider the prompt if it contains profanity or any other inappropriate content.
                              Only generate text messages to be sent via sms and not any other form of communication.",
                    ],
                ],
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
            ]);

            return str_replace("\n", "", $result->choices[0]->message->content);
        }
    }
    
}
