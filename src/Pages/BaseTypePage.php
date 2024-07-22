<?php

namespace TomatoPHP\FilamentTypes\Pages;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use TomatoPHP\FilamentIcons\Components\IconPicker;
use TomatoPHP\FilamentTypes\Components\TypeColumn;
use TomatoPHP\FilamentTypes\Models\Type;

class BaseTypePage extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    public array $data = [];

    protected static string $view = "filament-types::pages.base";

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function getBackUrl()
    {
        return url()->previous();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->form([
                    KeyValue::make('name')
                        ->schema($this->getLocalInputs())
                        ->keyLabel(trans('filament-ecommerce::messages.settings.status.columns.language'))
                        ->editableKeys(false)
                        ->addable(false)
                        ->deletable(false)
                        ->label(trans('filament-ecommerce::messages.settings.status.columns.value')),
                    TextInput::make('key'),
                    IconPicker::make('icon')->label(trans('filament-ecommerce::messages.settings.status.columns.icon')),
                    ColorPicker::make('color')->label(trans('filament-ecommerce::messages.settings.status.columns.color')),
                ])
                ->action(function (array $data){
                    $data['for'] = $this->getFor();
                    $data['type'] = $this->getType();
                    Type::create($data);

                    Notification::make()
                        ->title('Type Created')
                        ->body('The type has been created successfully.')
                        ->success()
                        ->send();
                }),
            Action::make('back')
                ->url(fn() => $this->getBackUrl())
                ->color('warning')
                ->icon('heroicon-s-arrow-left')
                ->label('Back')
        ];
    }

    public function getType(): string
    {
        return "status";
    }

    public function getFor(): string
    {
        return "types";
    }

    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected array $types = [];

    public function getTypes(): array
    {
        return  [];
    }

    public function mount(): void
    {
        foreach ($this->getTypes() as $type){
            $exists = Type::query()
                ->where('for', $this->getFor())
                ->where('type', $this->getType())
                ->where('key', $type->key)
                ->first();
            if(!$exists){
                $type->for = $this->getFor();
                $type->type =  $this->getType();
                $exists = Type::create($type->toArray());
            }
        }
    }

    public function getTitle(): string
    {
        return trans('filament-ecommerce::messages.settings.status.title');
    }

    private function getLocalInputs()
    {
        $localsTitle = [];
        foreach (config('filament-menus.locals') as $key=>$local){
            $localsTitle[] = TextInput::make($key)
                ->label($local[app()->getLocale()])
                ->required();
        }

        return $localsTitle;
    }

    public function getCreateAction(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Type::query()
                    ->where('for', $this->getFor())
                    ->where('type', $this->getType())
            )
            ->paginated(false)
            ->columns([
                TypeColumn::make('key')
                    ->label(trans('filament-ecommerce::messages.settings.status.columns.status'))
            ])
            ->actions([
                \Filament\Tables\Actions\Action::make('edit')
                    ->label(trans('filament-ecommerce::messages.settings.status.action.edit'))
                    ->tooltip(trans('filament-ecommerce::messages.settings.status.action.edit'))
                    ->form([
                        KeyValue::make('name')
                            ->schema($this->getLocalInputs())
                            ->keyLabel(trans('filament-ecommerce::messages.settings.status.columns.language'))
                            ->editableKeys(false)
                            ->addable(false)
                            ->deletable(false)
                            ->label(trans('filament-ecommerce::messages.settings.status.columns.value')),
                        IconPicker::make('icon')->label(trans('filament-ecommerce::messages.settings.status.columns.icon')),
                        ColorPicker::make('color')->label(trans('filament-ecommerce::messages.settings.status.columns.color')),
                    ])
                    ->extraModalFooterActions([
                        \Filament\Tables\Actions\Action::make('deleteType')
                            ->requiresConfirmation()
                            ->color('danger')
                            ->label('Delete')
                            ->cancelParentActions()
                            ->action(function (array $data, $record) {
                                foreach ($this->getTypes() as $getType){
                                    if($getType->key == $record->key){
                                        Notification::make()
                                            ->title('Type Deleted')
                                            ->body('The type is in use and cannot be deleted.')
                                            ->danger()
                                            ->send();

                                        return;
                                    }
                                }

                                $record->delete();
                                Notification::make()
                                    ->title('Type Deleted')
                                    ->body('The type has been deleted successfully.')
                                    ->success()
                                    ->send();
                            })
                    ])
                    ->fillForm(fn($record) => $record->toArray())
                    ->icon('heroicon-s-pencil-square')
                    ->iconButton()
                    ->action(function (array $data, Type $type){
                        $type->update($data);
                        Notification::make()
                            ->title(trans('filament-ecommerce::messages.settings.status.action.notification'))
                            ->success()
                            ->send();
                    })
            ]);
    }
}
