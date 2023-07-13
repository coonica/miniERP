<?php

namespace App\Http\Sections;

use AdminDisplay;
use AdminColumn;
use AdminForm;
use AdminFormElement;
use AdminColumnFilter;
use App\Models\Board;
use App\Models\BoardList;
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
 * Class ListCards
 *
 * @property \App\Models\ListCard $model
 *
 * @see https://sleepingowladmin.ru/#/ru/model_configuration_section
 */
class ListCards extends Section implements Initializable
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
        $this->addToNavigation()->setPriority(200)->setIcon('far fa-address-card');
    }

    /**
     * @param array $payload
     *
     * @return DisplayInterface
     */
    public function onDisplay($payload = [])
    {
        $columns = [
            AdminColumn::text('idCard', '#')->setWidth('125px')->setHtmlAttribute('class', 'text-center'),
            AdminColumn::url('urlSource', 'Ссылка')->setWidth('70px')->setHtmlAttribute('class', 'text-center')->setOrderable(false),
            AdminColumn::text('name', 'Название', 'updated_at')
                ->setSearchCallback(function($column, $query, $search){
                    return $query
                        ->orWhere('name', 'like', '%'.$search.'%')
                        ->orWhere('updated_at', 'like', '%'.$search.'%')
                    ;
                })
                ->setOrderable(function($query, $direction) {
                    $query->orderBy('name', $direction);
                }),
            AdminColumn::text('boardList.name', 'Список')->setWidth('90px')->setSearchable(false),
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
                ->setModelForOptions(boardList::class, 'idList')
                ->setLoadOptionsQueryPreparer(function($element, $query) {
                    return $query;
                })
                ->setDisplay('name')
                ->setColumnName('idList')
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
        $form = AdminForm::card()->addBody([
            AdminFormElement::columns()->addColumn([
                AdminFormElement::text('name', 'Название')->required(),
            ], 'col-xs-12 col-sm-6 col-md-8 col-lg-8')->addColumn([
                AdminFormElement::text('idCard', 'ID')->setReadonly(true),
            ], 'col-xs-12 col-sm-6 col-md-4 col-lg-4')
            ->addColumn([
                AdminFormElement::select('idBoard', 'Доска')->setModelForOptions(Board::class, 'name'),
            ], 'col-xs-12 col-sm-12 col-md-12 col-lg-12')
            ->addColumn([
                AdminFormElement::dependentselect('idList', 'Список')
                ->setModelForOptions(BoardList::class, 'name')
                ->setDataDepends(['idBoard'])
                ->setLoadOptionsQueryPreparer(function($item, $query) {
                    return $query->where('idBoard', $item->getDependValue('idBoard'));
                })
                ->required(),
            ], 'col-xs-12 col-sm-12 col-md-12 col-lg-12')
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
