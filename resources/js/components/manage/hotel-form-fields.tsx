import InputError from '@/components/input-error';
import CancellationPolicyEditor from '@/components/manage/cancellation-policy-editor';
import LocationPicker from '@/components/manage/location-picker';
import NativeSelect from '@/components/native-select';
import type { SelectOption } from '@/components/native-select';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';
import { amenityIcon } from '@/lib/amenity-icons';
import type { AmenityOption, CancellationTier, CategoryOption } from '@/types';

export type HotelFormValues = {
    name: string;
    description: string;
    province: string;
    municipality: string;
    address: string;
    latitude: number | null;
    longitude: number | null;
    stars: number | null;
    cancellation_policy: CancellationTier[];
    amenity_ids: number[];
    category_ids: number[];
};

type Props = {
    values: HotelFormValues;
    errors: Partial<Record<string, string>>;
    provinces: SelectOption[];
    amenities: AmenityOption[];
    categories: CategoryOption[];
};

/**
 * The address as typed in the form (not yet saved), for the map search.
 */
function addressForGeocoding(): string {
    const valueOf = (id: string) =>
        (document.getElementById(id) as HTMLInputElement | null)?.value ?? '';
    const province = document.getElementById(
        'province',
    ) as HTMLSelectElement | null;
    const provinceName = province?.value
        ? (province.selectedOptions[0]?.text ?? '')
        : '';

    return [valueOf('address'), valueOf('municipality'), provinceName]
        .filter(Boolean)
        .join(', ');
}

const textareaClasses =
    'flex min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm';

/**
 * Fields shared by the hotel create and edit forms.
 */
export default function HotelFormFields({
    values,
    errors,
    provinces,
    amenities,
    categories,
}: Props) {
    const { t } = useTranslation();

    const starOptions: SelectOption[] = [1, 2, 3, 4, 5].map((stars) => ({
        value: String(stars),
        label: '★'.repeat(stars),
    }));

    return (
        <div className="space-y-8">
            <section className="space-y-4">
                <div className="grid gap-2">
                    <Label htmlFor="name">{t('Hotel name')}</Label>
                    <Input
                        id="name"
                        name="name"
                        required
                        defaultValue={values.name}
                    />
                    <InputError message={errors.name} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="description">{t('Description')}</Label>
                    <textarea
                        id="description"
                        name="description"
                        required
                        maxLength={5000}
                        defaultValue={values.description}
                        className={textareaClasses}
                    />
                    <InputError message={errors.description} />
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="province">{t('Province')}</Label>
                        <NativeSelect
                            id="province"
                            name="province"
                            required
                            defaultValue={values.province}
                            placeholder={t('Choose a province')}
                            options={provinces}
                        />
                        <InputError message={errors.province} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="municipality">
                            {t('Municipality')}
                        </Label>
                        <Input
                            id="municipality"
                            name="municipality"
                            required
                            defaultValue={values.municipality}
                        />
                        <InputError message={errors.municipality} />
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-[1fr_10rem]">
                    <div className="grid gap-2">
                        <Label htmlFor="address">{t('Address')}</Label>
                        <Input
                            id="address"
                            name="address"
                            required
                            defaultValue={values.address}
                        />
                        <InputError message={errors.address} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="stars">{t('Stars')}</Label>
                        <NativeSelect
                            id="stars"
                            name="stars"
                            defaultValue={
                                values.stars ? String(values.stars) : ''
                            }
                            placeholder={t('No rating')}
                            options={starOptions}
                        />
                        <InputError message={errors.stars} />
                    </div>
                </div>
            </section>

            <LocationPicker
                initial={
                    values.latitude !== null && values.longitude !== null
                        ? {
                              latitude: values.latitude,
                              longitude: values.longitude,
                          }
                        : null
                }
                getAddress={addressForGeocoding}
                error={errors.latitude ?? errors.longitude}
            />

            <fieldset className="space-y-3">
                <legend className="text-sm font-medium">
                    {t('Amenities')}
                </legend>
                <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    {amenities.map((amenity) => {
                        const Icon = amenityIcon(amenity.icon);

                        return (
                            <Label
                                key={amenity.id}
                                className="flex cursor-pointer items-center gap-2 rounded-md border px-3 py-2 font-normal has-[[data-state=checked]]:border-primary"
                            >
                                <Checkbox
                                    name="amenity_ids[]"
                                    value={String(amenity.id)}
                                    defaultChecked={values.amenity_ids.includes(
                                        amenity.id,
                                    )}
                                />
                                <Icon className="size-4 text-muted-foreground" />
                                {amenity.name}
                            </Label>
                        );
                    })}
                </div>
                <InputError message={errors.amenity_ids} />
            </fieldset>

            <fieldset className="space-y-3">
                <legend className="text-sm font-medium">
                    {t('Categories')}
                </legend>
                <div className="flex flex-wrap gap-2">
                    {categories.map((category) => (
                        <Label
                            key={category.id}
                            className="flex cursor-pointer items-center gap-2 rounded-full border px-3 py-1.5 font-normal has-[[data-state=checked]]:border-primary"
                        >
                            <Checkbox
                                name="category_ids[]"
                                value={String(category.id)}
                                defaultChecked={values.category_ids.includes(
                                    category.id,
                                )}
                            />
                            {category.name}
                        </Label>
                    ))}
                </div>
                <InputError message={errors.category_ids} />
            </fieldset>

            <CancellationPolicyEditor
                initialTiers={values.cancellation_policy}
                errors={errors}
            />
        </div>
    );
}
