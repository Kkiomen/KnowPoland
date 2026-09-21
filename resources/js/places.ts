/**
 * The cities the site covers, in the order they are listed.
 *
 * One list, used in two places: the index page decides which entries link
 * somewhere, and a city page uses it to offer the next one. A city with no
 * `href` is listed but not yet written, which is honest and keeps the index
 * from looking like a stub.
 */
export type Place = {
    /** Translation key under `places.cities`. */
    key: string;
    /** Route of the city page, once it exists. */
    href?: string;
    /** Image slug: public/images/places-<photo>-{sm,lg}.jpg, once it exists. */
    photo?: string;
};

export const places: Place[] = [
    { key: 'gdansk', href: '/places/gdansk', photo: 'gdansk' },
    { key: 'warszawa', href: '/places/warszawa', photo: 'warszawa' },
    { key: 'krakow', href: '/places/krakow', photo: 'krakow' },
    { key: 'wroclaw', href: '/places/wroclaw', photo: 'wroclaw' },
    { key: 'lodz', href: '/places/lodz', photo: 'lodz' },
    { key: 'poznan', href: '/places/poznan', photo: 'poznan' },
    { key: 'szczecin', href: '/places/szczecin', photo: 'szczecin' },
    { key: 'bydgoszcz', href: '/places/bydgoszcz', photo: 'bydgoszcz' },
    { key: 'lublin', href: '/places/lublin', photo: 'lublin' },
    { key: 'katowice', href: '/places/katowice', photo: 'katowice' },
    { key: 'zakopane', href: '/places/zakopane', photo: 'zakopane' },
    { key: 'torun', href: '/places/torun', photo: 'torun' },
    { key: 'malbork', href: '/places/malbork', photo: 'malbork' },
    { key: 'wieliczka', href: '/places/wieliczka', photo: 'wieliczka' },
    { key: 'bialowieza', href: '/places/bialowieza', photo: 'bialowieza' },
    { key: 'mazury', href: '/places/mazury', photo: 'mazury' },
];

/** The next city to read, or undefined at the end of the list. */
export const nextPlaceAfter = (href: string): Place | undefined =>
    places
        .slice(places.findIndex((place) => place.href === href) + 1)
        .find((place) => place.href !== undefined);
