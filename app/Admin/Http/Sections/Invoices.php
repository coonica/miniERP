<?php

namespace App\Admin\Http\Sections;

use AdminColumn;
use AdminColumnFilter;
use AdminDisplay;
use AdminForm;
use AdminFormElement;
use App\Models\Board;
use App\Models\IncomeRate;
use App\Models\Invoice;
use App\Models\InvoiceTask;
use App\Models\Project;
use App\Models\Status;
use Composer\Config;
use Illuminate\Database\Eloquent\Model;
use SleepingOwl\Admin\Contracts\Display\DisplayInterface;
use SleepingOwl\Admin\Contracts\Form\FormInterface;
use SleepingOwl\Admin\Contracts\Initializable;
use SleepingOwl\Admin\Form\Buttons\Cancel;
use SleepingOwl\Admin\Form\Buttons\Save;
use SleepingOwl\Admin\Form\Buttons\SaveAndClose;
use SleepingOwl\Admin\Form\Buttons\SaveAndCreate;
use SleepingOwl\Admin\Section;

/**
 * Class Roles
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
        $this->addToNavigation()->setPriority(100)->setIcon('fas fa-file-invoice');
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
            AdminColumn::link('name', 'Name', 'created_at')
                ->setSearchCallback(function ($column, $query, $search) {
                    return $query
                        ->orWhere('name', 'like', '%' . $search . '%')
                        ->orWhere('created_at', 'like', '%' . $search . '%');
                })
                ->setOrderable(function ($query, $direction) {
                    $query->orderBy('created_at', $direction);
                })
            ,
            AdminColumn::text('created_at', 'Created / updated', 'updated_at')
                ->setWidth('160px')
                ->setOrderable(function ($query, $direction) {
                    $query->orderBy('updated_at', $direction);
                })
                ->setSearchable(false)
            ,
            AdminColumn::text('board.name', 'Board')->setWidth('200px')
            ,
            AdminColumn::text('project.name', 'Project')->setWidth('200px')
            ,
            AdminColumn::text('status.name', 'Status')->setWidth('200px')
            ,
        ];

        $display = AdminDisplay::datatablesAsync()
            ->setName('firstdatatables')
            ->with('invoiceTasks')
            ->setOrder([[0, 'asc']])
            ->setDisplaySearch(true)
            ->paginate(25)
            ->setColumns($columns)
            ->setHtmlAttribute('class', 'table-primary table-hover th-center');

        $display->setColumnFilters([
            AdminColumnFilter::select()
                ->setModelForOptions(\App\Models\Invoice::class, 'name')
                ->setLoadOptionsQueryPreparer(function ($element, $query) {
                    return $query;
                })
                ->setDisplay('name')
                ->setColumnName('name')
                ->setPlaceholder('All names')
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
        $formInvoice = AdminForm::form()->addElement(
            AdminFormElement::columns()
                ->addColumn([
                        AdminFormElement::text('name', 'Название счёта')
                        ,
                        AdminFormElement::select('project_id', 'Проект', Project::class)->setDisplay('name')
                            ->required()
                        ,
                        AdminFormElement::date('date', 'Дата')->setCurrentDate()
                        ,
                        AdminFormElement::select('idBoard', 'Боард', Board::class)->setDisplay('name')
                            ->required()
                        ,
                        AdminFormElement::select('status_id', 'Статус', Status::class)->setDisplay('name')
                            ->required()
                        ,
                    ]
                    , 'col-xs-12 col-sm-6 col-md-4 col-lg-4'),
        );

        $formInvoiceTask = AdminForm::form()->addElement(
            AdminFormElement::columns()
                ->addColumn([
                        AdminFormElement::select('invoice_id', 'Счёт', Invoice::class)->setDisplay('name')
                            ->required()
                        ,
                        AdminFormElement::textarea('note', 'Описание')
                        ,
                        AdminFormElement::text('fix_price', 'Бюджет', InvoiceTask::class)
                            ->required()
                        ,
                    ]
                    , 'col-xs-12 col-sm-6 col-md-4 col-lg-4'),
        );

        $tabs = AdminDisplay::tabbed();

        $tabs->appendTab($formInvoice, 'Invoice');
        $tabs->appendTab($formInvoiceTask, 'Invoice Task');


        return $tabs;
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
