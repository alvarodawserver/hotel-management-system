import { Head } from '@inertiajs/react';
import CategoryController from '@/actions/App/Http/Controllers/Admin/CategoryController';
import CataloguePage from '@/components/admin/catalogue-page';
import TranslatedNameFields from '@/components/admin/translated-name-fields';
import { useTranslation } from '@/hooks/use-translation';
import { index } from '@/routes/admin/categories';
import type { CatalogueEntry } from '@/types';

type Props = {
    categories: CatalogueEntry[];
};

export default function Categories({ categories }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Categories')} />

            <CataloguePage
                title={t('Categories')}
                description={t(
                    'Labels that describe the style of a hotel (luxury, boutique, family-friendly…). Owners choose them for their hotels.',
                )}
                entries={categories}
                newLabel={t('New category')}
                usageHeader={t('Hotels')}
                deleteDescription={(category) =>
                    category.usage_count > 0
                        ? t(
                              ':count hotels use it; it will be removed from them.',
                              { count: category.usage_count },
                          )
                        : t('No hotel uses it.')
                }
                fields={(category, errors) => (
                    <TranslatedNameFields
                        translations={category?.translations}
                        errors={errors}
                    />
                )}
                storeForm={CategoryController.store.form()}
                updateForm={(category) =>
                    CategoryController.update.form(category.id)
                }
                destroyForm={(category) =>
                    CategoryController.destroy.form(category.id)
                }
            />
        </>
    );
}

Categories.layout = {
    breadcrumbs: [{ title: 'Categories', href: index() }],
};
