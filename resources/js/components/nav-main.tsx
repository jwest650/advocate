import { SidebarGroup, SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui/sidebar';
import { useSidebarSettings } from '@/contexts/SidebarContext';
import { cn } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { ChevronRight } from 'lucide-react';

// Store expanded menu state in localStorage
const STORAGE_KEY = 'nav_expanded_items';

export function NavMain({ items = [], position }: { items: NavItem[]; position: 'left' | 'right' }) {
    const page = usePage();
    const { state } = useSidebar();
    const { style } = useSidebarSettings();

    // Colored/gradient sidebars paint over the theme tokens, so accents switch to translucent white
    const isTinted = style === 'colored' || style === 'gradient';

    // Check if the document is in RTL mode
    const isRtl = document.documentElement.dir === 'rtl';

    const [expandedItems, setExpandedItems] = useState<Record<string, boolean>>({});

    // Determine the actual position considering RTL mode
    const effectivePosition = isRtl ? (position === 'left' ? 'right' : 'left') : position;
    const isReversed = effectivePosition === 'right';

    // Initialize expanded state
    useEffect(() => {
        // Start with a clean slate - close all menus
        const newExpandedItems: Record<string, boolean> = {};

        // Process menus that should be expanded
        const processMenuItems = (menuItems: NavItem[], parentKey?: string) => {
            menuItems.forEach(item => {
                // If this is the active item or contains the active item
                const isItemActive = isActive(item.href);
                const hasActiveChild = item.children && isChildActive(item.children);

                // If this item or its children are active, expand it
                if (parentKey && (isItemActive || hasActiveChild)) {
                    newExpandedItems[parentKey] = true;
                }

                // If this item has children and is active, has active children, or defaultOpen is true, expand it
                if (item.children && (isItemActive || hasActiveChild || item.defaultOpen === true)) {
                    newExpandedItems[item.title] = true;

                    // Recursively check children
                    processMenuItems(item.children, item.title);
                }

                // Check nested children with their own keys
                if (item.children) {
                    checkNestedChildren(item.children, 1, newExpandedItems);
                }
            });
        };

        processMenuItems(items);

        // Update state and save to localStorage
        setExpandedItems(newExpandedItems);
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(newExpandedItems));
        } catch (e) {
            console.error('Error saving navigation state:', e);
        }
    }, [page.url, items]); // Re-run when URL changes or items change

    // Helper function to check nested children for active items
    const checkNestedChildren = (
        children: NavItem[],
        level: number,
        newExpandedItems: Record<string, boolean>
    ) => {
        children.forEach(child => {
            const childKey = `${level}-${child.title}`;
            const isChildItemActive = isActive(child.href);
            const hasActiveChild = child.children && isChildActive(child.children);

            if (child.children && (isChildItemActive || hasActiveChild)) {
                newExpandedItems[childKey] = true;
                checkNestedChildren(child.children, level + 1, newExpandedItems);
            }
        });
    };

    const toggleExpand = (title: string) => {
        const newExpandedItems = {
            ...expandedItems,
            [title]: !expandedItems[title]
        };

        setExpandedItems(newExpandedItems);

        // Save to localStorage
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(newExpandedItems));
        } catch (e) {
            console.error('Error saving navigation state:', e);
        }
    };

    const isActive = (href?: string) => {
        if (!href) return false;

        // Extract pathname from href if it's a full URL
        const hrefPath = href.startsWith('http') ? new URL(href).pathname : href;
        const currentPath = page.url;

        const active = currentPath === hrefPath || currentPath.startsWith(hrefPath + '/');
        return active;
    };

    const isChildActive = (children?: NavItem[]) => {
        if (!children) return false;
        return children.some(child => isActive(child.href) || isChildActive(child.children));
    };

    const isCollapsed = state === 'collapsed';

    /* ── Shared class recipes ─────────────────────────────── */

    const topButtonClass = (active: boolean) =>
        cn(
            'nav-item group/nav relative h-10 rounded-xl px-2.5 font-medium transition-all duration-300',
            isCollapsed && 'justify-center',
            isTinted
                ? 'text-white/80 hover:bg-white/10 hover:text-white'
                : 'text-sidebar-foreground/80 hover:bg-sidebar-accent hover:text-sidebar-foreground',
            active &&
                (isTinted
                    ? 'bg-white/[0.18] text-white shadow-sm'
                    : 'bg-primary/10 text-primary shadow-[inset_0_1px_0_0_var(--color-sidebar)]'),
        );

    const iconBoxClass = (active: boolean) =>
        cn(
            'flex h-7 w-7 shrink-0 items-center justify-center rounded-lg transition-all duration-300 group-hover/nav:scale-105',
            active
                ? isTinted
                    ? 'bg-white/25 text-white'
                    : 'bg-primary/15 text-primary'
                : isTinted
                  ? 'text-white/70 group-hover/nav:bg-white/10'
                  : 'text-sidebar-foreground/60 group-hover/nav:bg-sidebar-accent group-hover/nav:text-sidebar-foreground',
        );

    const subLinkClass = (active: boolean) =>
        cn(
            'nav-sub-item group/sub relative flex h-8 items-center gap-2.5 rounded-lg px-2 text-[13px] transition-all duration-300 outline-none focus-visible:ring-2 focus-visible:ring-sidebar-ring',
            isReversed ? 'flex-row-reverse text-right' : 'text-left',
            isTinted
                ? active
                    ? 'bg-white/[0.16] font-semibold text-white'
                    : 'text-white/70 hover:bg-white/10 hover:text-white'
                : active
                  ? 'bg-primary/10 font-semibold text-primary'
                  : 'text-sidebar-foreground/70 hover:bg-sidebar-accent hover:text-sidebar-foreground',
        );

    const dotClass = (active: boolean) =>
        cn(
            'h-1.5 w-1.5 shrink-0 rounded-full transition-all duration-300',
            active
                ? isTinted
                    ? 'bg-white ring-[3px] ring-white/20'
                    : 'bg-primary ring-[3px] ring-primary/20'
                : isTinted
                  ? 'bg-white/30 group-hover/sub:bg-white/60'
                  : 'bg-sidebar-foreground/25 group-hover/sub:bg-sidebar-foreground/50',
        );

    const chevronClass = (expanded: boolean) =>
        cn(
            'h-3.5 w-3.5 shrink-0 opacity-60 transition-transform duration-300',
            expanded ? 'rotate-90' : isRtl ? 'rotate-180' : '',
        );

    /* ── Submenu ──────────────────────────────────────────── */

    const renderSubMenu = (children: NavItem[], level: number = 1) => (
        <div className="nav-sub-panel relative overflow-hidden">
            {/* Guide rail */}
            <span
                className={cn(
                    'absolute inset-y-1 w-px',
                    isReversed ? 'end-[1.15rem]' : 'start-[1.15rem]',
                    isTinted ? 'bg-white/20' : 'bg-sidebar-border',
                )}
            />
            <ul
                data-sidebar="menu-sub"
                className={cn('flex min-w-0 flex-col gap-0.5 py-1', isReversed ? 'me-8 ms-0' : 'ms-8 me-0')}
            >
                {children.map(child => {
                    const childKey = `${level}-${child.title}`;
                    const childExpanded = !!expandedItems[childKey];

                    return (
                        <li key={child.title} className="min-w-0">
                            {child.children ? (
                                <>
                                    <button
                                        type="button"
                                        onClick={() => toggleExpand(childKey)}
                                        className={cn(subLinkClass(isChildActive(child.children)), 'w-full')}
                                    >
                                        <span className={dotClass(isChildActive(child.children))} />
                                        <span className="flex-1 truncate">{child.title}</span>
                                        {!isCollapsed && <ChevronRight className={chevronClass(childExpanded)} />}
                                    </button>

                                    {/* Render nested children */}
                                    {childExpanded && renderSubMenu(child.children, level + 1)}
                                </>
                            ) : (
                                <Link
                                    href={child.href || '#'}
                                    target={child.target}
                                    className={subLinkClass(isActive(child.href))}
                                >
                                    <span className={dotClass(isActive(child.href))} />
                                    <span className="flex-1 truncate">{child.title}</span>
                                </Link>
                            )}
                        </li>
                    );
                })}
            </ul>
        </div>
    );

    /* ── Top level row content ────────────────────────────── */

    const renderTopContent = (item: NavItem, active: boolean, expanded?: boolean, showChevron = false) => {
        if (isCollapsed) {
            return item.icon ? <item.icon className="h-[18px] w-[18px]" /> : null;
        }

        return (
            <div className={cn('flex w-full min-w-0 items-center gap-2.5', isReversed && 'flex-row-reverse text-right')}>
                {item.icon && (
                    <span className={iconBoxClass(active)}>
                        <item.icon className="h-4 w-4" />
                    </span>
                )}
                <span className="min-w-0 flex-1 truncate">{item.title}</span>
                {item.badge && (
                    <span
                        className={cn(
                            'shrink-0 rounded-full px-1.5 py-0.5 text-[10px] font-semibold',
                            isTinted ? 'bg-white/25 text-white' : 'bg-primary text-primary-foreground',
                        )}
                    >
                        {item.badge.label}
                    </span>
                )}
                {showChevron && <ChevronRight className={chevronClass(!!expanded)} />}
            </div>
        );
    };

    return (
        <SidebarGroup className="px-2 py-0">
            {/* Scoped nav animations */}
            <style>{`
                @keyframes navSubIn {
                    from { opacity: 0; transform: translateY(-6px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                .nav-sub-panel { animation: navSubIn .28s cubic-bezier(.16,.84,.44,1); }
                .nav-item::before {
                    content: '';
                    position: absolute;
                    inset-inline-start: 0;
                    top: 50%;
                    height: 0;
                    width: 3px;
                    border-radius: 9999px;
                    background: currentColor;
                    opacity: 0;
                    transform: translateY(-50%);
                    transition: height .3s cubic-bezier(.16,.84,.44,1), opacity .3s ease;
                }
                .nav-item[data-active="true"]::before { height: 1.25rem; opacity: 1; }
                .nav-rail-end .nav-item::before { inset-inline-start: auto; inset-inline-end: 0; }
                @media (prefers-reduced-motion: reduce) {
                    .nav-sub-panel { animation: none; }
                    .nav-item::before { transition: none; }
                }
            `}</style>

            {!isCollapsed && (
                <div
                    className={cn(
                        'flex items-center gap-2 px-1 pt-1 pb-2',
                        isReversed ? 'flex-row-reverse' : '',
                    )}
                >
                    <span
                        className={cn(
                            'text-[10px] font-semibold tracking-[0.16em] uppercase',
                            isTinted ? 'text-white/50' : 'text-sidebar-foreground/50',
                        )}
                    >
                        Platform
                    </span>
                    <span
                        className={cn(
                            'h-px flex-1',
                            isTinted
                                ? 'bg-gradient-to-r from-white/25 to-transparent'
                                : 'bg-gradient-to-r from-sidebar-border to-transparent',
                        )}
                    />
                </div>
            )}

            <SidebarMenu className={cn('gap-1', isReversed && 'nav-rail-end')}>
                {items.map((item) => {
                    const hasChildren = !!item.children;
                    const active = hasChildren ? !!isChildActive(item.children) : isActive(item.href);
                    const expanded = !!expandedItems[item.title];

                    return (
                        <div key={item.title}>
                            {hasChildren ? (
                                // Parent item with children
                                <>
                                    <SidebarMenuItem>
                                        <SidebarMenuButton
                                            isActive={active}
                                            tooltip={{ children: item.title }}
                                            onClick={() => toggleExpand(item.title)}
                                            className={topButtonClass(active)}
                                        >
                                            {renderTopContent(item, active, expanded, !isCollapsed)}
                                        </SidebarMenuButton>
                                    </SidebarMenuItem>

                                    {/* Child items */}
                                    {!isCollapsed && expanded && renderSubMenu(item.children!)}
                                </>
                            ) : (
                                // Regular item without children
                                <SidebarMenuItem>
                                    <SidebarMenuButton
                                        asChild
                                        isActive={active}
                                        tooltip={{ children: item.title }}
                                        className={topButtonClass(active)}
                                    >
                                        {item.target === '_blank' ? (
                                            <a href={item.href || '#'} target="_blank" rel="noopener noreferrer">
                                                {renderTopContent(item, active)}
                                            </a>
                                        ) : (
                                            <Link href={item.href || '#'}>
                                                {renderTopContent(item, active)}
                                            </Link>
                                        )}
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            )}
                        </div>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
