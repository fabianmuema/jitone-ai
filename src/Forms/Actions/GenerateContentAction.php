<?php

namespace Jiten14\JitoneAi\Forms\Actions;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Jiten14\JitoneAi\JitoneAi;
use Filament\Notifications\Notification;

class GenerateContentAction
{
    public function execute($field, $record, $data, array $options = [])
    {
        return Action::make('generateContent')
            ->label('Generate with Centipid AI')
            ->icon('heroicon-s-sparkles')
            ->form([
                Select::make('existing_content_action')
                    ->label('Action on existing content')
                    ->options([
                        'refine' => 'Refine',
                        'expand' => 'Expand',
                        'shorten' => 'Shorten',
                    ])
                    ->visible(fn (callable $get) => $get('use_existing_content'))
                    ->required(fn (callable $get) => $get('use_existing_content')),
                Textarea::make('ai_prompt')
                    ->label('Enter your prompt')
                    ->required()
                    ->placeholder(fn ($get) => $get('ai_prompt_placeholder') ?? 'Write your sms prompt here. eg. Write a short sms notifying clients of a scheduled maintenance.')
                    ->visible(fn (callable $get) => !$get('use_existing_content')),
//                Select::make('template')
//                    ->label('Or choose a template')
//                    ->options(function () {
//                        return app(JitoneAi::class)->getContentTemplates();
//                    })
//                    ->reactive()
//                    ->afterStateUpdated(function ($state, callable $set) {
//                        $placeholders = app(JitoneAi::class)->getTemplatesPlaceholders();
//                        if ($state) {
//                            $placeholder = $placeholders[$state] ?? 'Write your content here';
//                            $set('ai_prompt_placeholder', $placeholder);
//                        }
//                    })
//                    ->visible(fn (callable $get) => !$get('use_existing_content')),
            ])
            ->action(function (array $data) use ($field, $options) {
                if (!env('OPENAI_API_KEY')) {
                    Notification::make()
                        ->warning()
                        ->title('OpenAI API Key Missing')
                        ->body('Please add your OpenAI API Key to the .env file before proceeding.')
                        ->send();
                    return;
                }

                try {
                    $currentContent = $field->getState();

                        $prompt = $data['ai_prompt'] ?? null;

                        if (empty($prompt)) {
                            throw new \Exception("Prompt is empty or null. Form data: " . json_encode($data));
                        }


                    $generatedContent = app(JitoneAi::class)->generateContent($prompt, $options);
                    
                    $textInputContent = $generatedContent;
                    // Remove incomplete sentences
                    $generatedContent = $this->removeIncompleteSentences($generatedContent);

                        // Append the new content to the existing content for non-existing content actions
                        if ($field instanceof RichEditor) {
                            $newContent = $currentContent . "\n\n" . $generatedContent;
                        } elseif ($field instanceof Textarea) {
                            $newContent = $currentContent . "\n" . $generatedContent;
                        } else {
                            $newContent = trim($currentContent . ' ' . $textInputContent);
                        }

                    
                    // Set the new content
                    $field->state($newContent);

                    // Notify the user of successful content generation
                    Notification::make()
                        ->success()
                        ->title('Content Generated Successfully')
                        ->body('The AI-generated content has been added to the field.')
                        ->send();

                } catch (\Exception $e) {
                    // Notify the user if an error occurs
                    Notification::make()
                        ->danger()
                        ->title('Error Generating Content')
                        ->body('An error occurred while generating content: ' . $e->getMessage())
                        ->send();
                }
            })
            ->modalHeading('Generate Content with AI')
            ->modalButton('Generate');
    }

    private function removeIncompleteSentences($content)
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $lastSentence = end($sentences);
        
        // Check if the last sentence ends with a period, exclamation mark, or question mark
        if (!preg_match('/[.!?]$/', $lastSentence)) {
            // Remove the last sentence if it's incomplete
            array_pop($sentences);
        }
        
        return implode(' ', $sentences);
    }
}
