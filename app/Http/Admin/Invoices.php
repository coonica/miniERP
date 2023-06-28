<?php

namespace App\Http\Admin;

use AdminDisplay;
use AdminColumn;
use AdminForm;
use AdminFormElement;
use AdminColumnFilter;
use App\Models\Board;
use App\Models\Project;
use App\Models\Status;
use SleepingOwl\Admin\Contracts\Display\DisplayInterface;
use SleepingOwl\Admin\Contracts\Form\FormInterface;
use SleepingOwl\Admin\Section;
use Illuminate\Database\Eloquent\Model;
use SleepingOwl\Admin\Contracts\Initializable;
use SleepingOwl\Admin\Form\Buttons\Save;
use SleepingOwl\Admin\Form\Buttons\SaveAndClose;
use SleepingOwl\Admin\Form\Buttons\Cancel;
use SleepingOwl\Admin\Form\Buttons\SaveAndCreate;

/**
 * Class Invoices
 *
 * @property \App\Models\Invoice $model
 *
 * @see https://sleepingowladmin.ru/#/ru/model_configuration_section
 */
class Invoices extends Section implements Initializable
{
    /**
     * @var bool
     */
    protected $checkAccess = false;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $alias;

    /**
     * Initialize class.
     */
    public function initialize()
    {
        $this->addToNavigation()->setPriority(110)->setIcon('fas fa-file-invoice-dollar');
    }

    /**
     * @param array $payload
     *
     * @return DisplayInterface
     */
    public function onDisplay($payload = [])
    {
        $columns = [
            AdminColumn::text('id', '#')->setWidth('50px')->setHtmlAttribute('class', 'text-center'),
            AdminColumn::link('name', 'Название')
            ->setWidth('130px')
            ->setSearchCallback(function($column, $query, $search){
                return $query
                    ->orWhere('name', 'like', '%'.$search.'%')
                ;
            }),
            AdminColumn::text('project.name', 'Проект')->setWidth('130px'),
            AdminColumn::text('board.name', 'Доска')->setWidth('130px'),
            AdminColumn::datetime('date', 'Дата')
            ->setFormat('d.m.Y')
            ->setWidth('100px')
            ->setOrderable(function($query, $direction) {
                $query->orderBy('updated_at', $direction);
            })
            ->setSearchable(false)
        ,
            AdminColumn::lists('invoiceTasks.note', 'Задачи'),
            AdminColumn::text('status.name', 'Статус')->setWidth('130px'),
        ];

        $display = AdminDisplay::datatables()
            ->setName('firstdatatables')
            ->setOrder([[0, 'asc']])
            ->setDisplaySearch(true)
            ->paginate(25)
            ->setColumns($columns)
            ->setHtmlAttribute('class', 'table-primary table-hover th-center')
        ;

        $display->setColumnFilters([
            AdminColumnFilter::select()
                ->setModelForOptions(\App\Models\Status::class, 'id')
                ->setLoadOptionsQueryPreparer(function($element, $query) {
                    return $query;
                })
                ->setDisplay('name')
                ->setColumnName('status.id')
                ->setPlaceholder('Все статусы')
            ,
        ]);
        $display->getColumnFilters()->setPlacement('card.heading');

        return $display;
    }

    /**
     * @param int|null $id
     * @param array $payload
     *
     * @return FormInterface
     */
    public function onEdit($id = null, $payload = [])
    {
        $tabs = AdminDisplay::tabbed();
        $tabs->setTabs(function ($id) {
            $tabs = [];

            $tabs[] = AdminDisplay::tab(AdminForm::elements([
                AdminFormElement::text('id', 'ID')->setReadonly(true),
                AdminFormElement::text('name', 'Счет'),
                AdminFormElement::select('project_id', 'Проект', Project::class)->setDisplay('name')->required(),
                AdminFormElement::select('idBoard', 'Доска', Board::class)->setDisplay('name')->required(),
                AdminFormElement::datetime('date', 'Дата')->required(),
                AdminFormElement::select('status_id', 'Статус', Status::class)->setDisplay('name')->required(),
                ]))->setLabel('Счета');

            $tabs[] = AdminDisplay::tab(new \SleepingOwl\Admin\Form\FormElements([
                AdminFormElement::hasMany('invoiceTasks', [
                AdminFormElement::textarea('note', 'Описание')->required(),
                AdminFormElement::text('fix_price', 'Стоимость'),
                ])
            ]))->setLabel('Задачи счетов');

            return $tabs;
        });

        $form = AdminForm::card()
        ->addHeader([
            $tabs
        ]);

        $form->getButtons()->setButtons([
            'save'  => new Save(),
            'save_and_close'  => new SaveAndClose(),
            'save_and_create'  => new SaveAndCreate(),
            'cancel'  => (new Cancel()),
        ]);

        return $form;
    }

    /**
     * @return FormInterface
     */
    public function onCreate($payload = [])
    {
        return $this->onEdit(null, $payload);
    }

    /**
     * @return bool
     */
    public function isDeletable(Model $model)
    {
        return true;
    }

    /**
     * @return void
     */
    public function onRestore($id)
    {
        // remove if unused
    }
}
