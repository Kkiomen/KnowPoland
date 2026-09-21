/**
 * The reading order of the history section.
 *
 * One list, used in two places: the era page decides which topics already
 * link to an article, and every article page uses it to offer the next one.
 * Adding an article means adding a line here and nothing else.
 */
export type Article = {
    /** Route of the article. */
    href: string;
    /** Translation key holding its title. */
    titleKey: string;
    /** Era it sits in, and the topic position inside that era, when it has one. */
    era?: string;
    index?: number;
    /**
     * Further topic positions the same article answers, for the cases where one
     * page covers several lines of an era band instead of one.
     */
    alsoIndexes?: number[];
    /**
     * Where the article hangs off the Second World War overview, written as
     * '<chapter>.<index>', for example 'invasion.0'.
     *
     * The era bands on /history have a fixed number of slots and the war era
     * has run out of them, so the deep articles about a single wartime subject
     * are reached from the topic list inside a chapter of the war overview
     * article instead. An article can carry `warTopic` on its own, with no `era` or
     * `index`: it then has no line on the era page, keeps its place in the
     * reading order, and turns one overview topic into a link.
     */
    warTopic?: string;
    /**
     * Further overview topics the same article answers, written the same way as
     * `warTopic`. This is the war-overview twin of `alsoIndexes`: an article deep
     * enough to cover a whole chapter claims every topic in it, so none of those
     * lines is left as dead text while the article that answers it sits one click
     * away.
     */
    alsoWarTopics?: string[];
};

export const articles: Article[] = [
    {
        href: '/history/how-poland-began',
        titleKey: 'origins.meta.title',
        era: 'beginnings',
        index: 0,
    },
    {
        href: '/history/lech-and-the-white-eagle',
        titleKey: 'eagle.meta.title',
        era: 'beginnings',
        index: 1,
    },
    {
        href: '/history/baptism-of-966',
        titleKey: 'baptism.meta.title',
        era: 'beginnings',
        index: 2,
    },
    {
        href: '/history/gniezno-the-first-capital',
        titleKey: 'gniezno.meta.title',
        era: 'beginnings',
        index: 3,
    },
    {
        href: '/history/casimir-the-great',
        titleKey: 'casimir.meta.title',
        era: 'beginnings',
        index: 4,
    },
    {
        href: '/history/where-to-stand-in-it',
        titleKey: 'where.meta.title',
        era: 'beginnings',
        index: 5,
    },
    {
        href: '/history/grunwald-1410',
        titleKey: 'grunwald.meta.title',
        era: 'commonwealth',
        index: 0,
    },
    {
        href: '/history/the-golden-age',
        titleKey: 'golden.meta.title',
        era: 'commonwealth',
        index: 1,
    },
    {
        href: '/history/one-state-many-faiths',
        titleKey: 'faiths.meta.title',
        era: 'commonwealth',
        index: 2,
    },
    {
        href: '/history/vienna-1683',
        titleKey: 'vienna.meta.title',
        era: 'commonwealth',
        index: 3,
    },
    {
        href: '/history/serfdom',
        titleKey: 'serfdom.meta.title',
        era: 'commonwealth',
        index: 4,
    },
    {
        href: '/history/liberum-veto',
        titleKey: 'veto.meta.title',
        era: 'commonwealth',
        index: 5,
    },
    {
        href: '/history/constitution-of-3-may-1791',
        titleKey: 'constitution.meta.title',
        era: 'commonwealth',
        index: 6,
    },
    {
        href: '/history/first-partition-1772',
        titleKey: 'partition1.meta.title',
        era: 'partitions',
        index: 0,
    },
    {
        href: '/history/second-partition-1793',
        titleKey: 'partition2.meta.title',
        era: 'partitions',
        index: 1,
    },
    {
        href: '/history/kosciuszko-1794',
        titleKey: 'kosciuszko.meta.title',
        era: 'partitions',
        index: 2,
    },
    {
        href: '/history/third-partition-1795',
        titleKey: 'partition3.meta.title',
        era: 'partitions',
        index: 3,
    },
    {
        href: '/history/uprisings-1830-1863',
        titleKey: 'uprisings.meta.title',
        era: 'partitions',
        index: 4,
    },
    {
        href: '/history/keeping-a-country',
        titleKey: 'keeping.meta.title',
        era: 'partitions',
        index: 5,
    },
    {
        href: '/history/three-empires-three-cities',
        titleKey: 'empires.meta.title',
        era: 'partitions',
        index: 6,
    },
    {
        href: '/history/second-republic-1918',
        titleKey: 'republic.meta.title',
        era: 'wars',
        index: 0,
    },
    {
        href: '/history/battle-of-warsaw-1920',
        titleKey: 'war1920.meta.title',
        era: 'wars',
        index: 1,
    },
    {
        href: '/history/september-1939',
        titleKey: 'sept1939.meta.title',
        era: 'wars',
        index: 2,
        warTopic: 'invasion.2',
    },
    {
        href: '/history/free-city-of-danzig',
        titleKey: 'danzig.meta.title',
        // no era band slot left: it is reached from the war overview instead
        warTopic: 'invasion.0',
    },
    {
        href: '/history/westerplatte',
        titleKey: 'westerplatte.meta.title',
        // no era band slot left: it is reached from the war overview instead
        warTopic: 'invasion.1',
    },
    {
        href: '/history/molotov-ribbentrop-pact',
        titleKey: 'pact.meta.title',
        // no era band slot left: it is reached from the war overview instead
        warTopic: 'invasion.3',
    },
    {
        href: '/history/government-in-exile',
        titleKey: 'exile.meta.title',
        // no era band slot left: it is reached from the war overview instead
        warTopic: 'invasion.4',
    },
    {
        href: '/history/general-government',
        titleKey: 'gg.meta.title',
        // no era band slot left: it is reached from the war overview instead
        warTopic: 'occupation.0',
        alsoWarTopics: ['occupation.1'],
    },
    {
        href: '/history/katyn',
        titleKey: 'katyn.meta.title',
        // no era band slot left: it is reached from the war overview instead
        warTopic: 'occupation.2',
    },
    {
        href: '/history/zamosc-expulsions',
        titleKey: 'zamosc.meta.title',
        // no era band slot left: it is reached from the war overview instead
        warTopic: 'occupation.3',
    },
    {
        href: '/history/volhynia-1943',
        titleKey: 'volhynia.meta.title',
        // no era band slot left: it is reached from the war overview instead
        warTopic: 'occupation.4',
    },
    {
        href: '/history/the-ghettos',
        titleKey: 'ghettos.meta.title',
        // no era band slot left: it is reached from the war overview instead
        warTopic: 'holocaust.0',
    },
    {
        href: '/history/the-death-camps',
        titleKey: 'camps.meta.title',
        // no era band slot left: it is reached from the war overview instead
        warTopic: 'holocaust.1',
    },
    {
        href: '/history/warsaw-ghetto-uprising',
        titleKey: 'ghettouprising.meta.title',
        // no era band slot left: it is reached from the war overview instead
        warTopic: 'holocaust.2',
    },
    {
        href: '/history/underground-state',
        titleKey: 'underground.meta.title',
        // no era band slot left: it is reached from the war overview instead
        warTopic: 'resistance.0',
    },
    {
        href: '/history/witold-pilecki',
        titleKey: 'pilecki.meta.title',
        // no era band slot left: it is reached from the war overview instead
        warTopic: 'resistance.1',
    },
    {
        href: '/history/jan-karski',
        titleKey: 'karski.meta.title',
        // no era band slot left: it is reached from the war overview instead
        warTopic: 'resistance.2',
    },
    {
        href: '/history/enigma',
        titleKey: 'enigma.meta.title',
        // no era band slot left: it is reached from the war overview instead
        warTopic: 'resistance.3',
    },
    {
        href: '/history/polish-forces-abroad',
        titleKey: 'forces.meta.title',
        // no era band slot left: it is reached from the war overview instead
        warTopic: 'resistance.4',
    },
    {
        href: '/history/warsaw-uprising-1944',
        titleKey: 'rising.meta.title',
        // no era band slot left: it is reached from the war overview instead,
        // and it answers that whole chapter rather than one line of it
        warTopic: 'uprising.0',
        alsoWarTopics: ['uprising.1', 'uprising.2', 'uprising.3', 'uprising.4'],
    },
    {
        href: '/history/occupation-and-holocaust',
        titleKey: 'occupation.meta.title',
        era: 'wars',
        index: 3,
        warTopic: 'holocaust.3',
        alsoWarTopics: ['holocaust.4', 'holocaust.5'],
    },
    {
        href: '/history/second-world-war',
        titleKey: 'war.meta.title',
        era: 'wars',
        // every chapter of it now has at least one deep article hanging off its
        // topic list, so the overview is the map rather than the whole story
        index: 4,
    },
    {
        href: '/history/the-borders-moved',
        titleKey: 'borders.meta.title',
        era: 'communism',
        index: 0,
        warTopic: 'aftermath.0',
        alsoWarTopics: ['aftermath.2'],
    },
    {
        href: '/history/rebuilding-warsaw',
        titleKey: 'rebuilding.meta.title',
        era: 'communism',
        index: 1,
        warTopic: 'aftermath.3',
    },
    {
        href: '/history/solidarity-1980',
        titleKey: 'solidarity.meta.title',
        era: 'communism',
        index: 2,
    },
    {
        href: '/history/june-1989',
        titleKey: 'june1989.meta.title',
        era: 'communism',
        index: 3,
    },
    {
        href: '/history/the-transition',
        titleKey: 'transition.meta.title',
        era: 'after',
        index: 0,
    },
    {
        href: '/history/nato-and-the-eu',
        titleKey: 'euro.meta.title',
        era: 'after',
        index: 1,
    },
    {
        href: '/history/poland-since-2004',
        titleKey: 'today.meta.title',
        era: 'after',
        index: 2,
    },
];

/** The article a topic links to, or undefined while it is still unwritten. */
export const articleFor = (era: string, index: number): Article | undefined =>
    articles.find(
        (article) =>
            article.era === era &&
            (article.index === index ||
                (article.alsoIndexes?.includes(index) ?? false)),
    );

/**
 * The article a topic of the war overview links to, or undefined while it is
 * still unwritten. Same shape as articleFor, one level deeper: the war page
 * asks for a chapter key and the position of the topic inside that chapter.
 */
export const articleForWarTopic = (
    chapter: string,
    index: number,
): Article | undefined => {
    const topic = `${chapter}.${index}`;

    return articles.find(
        (article) =>
            article.warTopic === topic ||
            (article.alsoWarTopics?.includes(topic) ?? false),
    );
};

/** The next article to read, or undefined at the end of the section. */
export const nextAfter = (href: string): Article | undefined =>
    articles[articles.findIndex((article) => article.href === href) + 1];
