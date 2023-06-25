<?php

namespace App\Http\Sections;

use AdminColumn;
use AdminColumnFilter;
use AdminDisplay;
use AdminForm;
use AdminFormElement;
use App\Models\ListCard;
use App\Models\User;
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
 * Class Members
 *
 * @property \App\Models\Member $model
 *
 * @see https://sleepingowladmin.ru/#/ru/model_configuration_section
 */
class Members extends Section implements Initializable
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
        $this->addToNavigation()->setPriority(100)->setIcon('fas fa-user');
    }

    /**
     * @param array $payload
     *
     * @return DisplayInterface
     */
    public function onDisplay($payload = [])
    {
        $columns = [
            AdminColumn::text('id', '#')->setWidth('300px')->setHtmlAttribute('class', 'text-center'),
            AdminColumn::link('user.name', 'User')->setWidth('200px')->setHtmlAttribute('class', 'text-center') //can change to related link in future
                ->setSearchCallback(function ($column, $query, $search) {
                    return $query
                        ->orWhere('user_name', 'like', '%' . $search . '%')
                        ->orWhere('created_at', 'like', '%' . $search . '%');
                })
                ->setOrderable(function ($query, $direction) {
                    $query->orderBy('user_name', $direction);
                })
            ,
            AdminColumn::text('rate', 'Rate')->setWidth('100px')->setOrderable(false)
                ->setHtmlAttribute('class', 'text-center'),
            AdminColumn::lists('listCards.name', 'Cards<br/><small>(list)</small>')
                ->setHtmlAttribute('class', 'text-center')
                ->setWidth('300px'),

            AdminColumn::text('created_at', 'Created / updated', 'updated_at')
                ->setWidth('160px')
                ->setOrderable(function ($query, $direction) {
                    $query->orderBy('updated_at', $direction);
                })
                ->setSearchable(false)
                ->setHtmlAttribute('class', 'text-center')
            ,

        ];

        $display = AdminDisplay::datatables()
            ->setName('firstdatatables')
            ->setOrder([[0, 'asc']])
            ->setDisplaySearch(true)
            ->paginate(25)
            ->setColumns($columns)
            ->setHtmlAttribute('class', 'table-primary table-hover th-center');

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
            AdminFormElement::columns()
                ->addColumn([
                    AdminFormElement::select('user_id', 'User', User::class)->setDisplay('name')
                        ->required()
                    ,
                    AdminFormElement::html('<hr>'),
                    AdminFormElement::datetime('created_at')
                        ->setVisible(true)
                        ->setReadonly(false)
                    ,
                    AdminFormElement::html('Creating date')
                ], 'col-xs-12 col-sm-6 col-md-4 col-lg-4')
                ->addColumn([
                    AdminFormElement::text('id', 'ID')->setReadonly(true),
                    AdminFormElement::html('(Can\'t change)'),
                    AdminFormElement::html('<hr>'),
                    AdminFormElement::text('rate', 'Rate'),
                    AdminFormElement::html('Type number in decimal format'),
                    AdminFormElement::html('<hr>'),
                    AdminFormElement::multiselect('listCards', 'Cards', ListCard::class)->setDisplay('name'),


                ], 'col-xs-12 col-sm-6 col-md-8 col-lg-8'),
        ]);

        $form->getButtons()->setButtons([
            'save' => new Save(),
            'save_and_close' => new SaveAndClose(),
            'save_and_create' => new SaveAndCreate(),
            'cancel' => (new Cancel()),
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
