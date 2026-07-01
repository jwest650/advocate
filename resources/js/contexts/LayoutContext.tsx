import { createContext, ReactNode, useContext, useEffect, useState } from 'react';
import { getCookie, setCookie, isDemoMode } from '@/utils/cookies';

export type LayoutPosition = 'left' | 'right';

type LayoutContextType = {
    position: LayoutPosition;
    effectivePosition: LayoutPosition;
    updatePosition: (val: LayoutPosition) => void;
    isRtl: boolean;
    saveLayoutPosition: (position: LayoutPosition) => void;
};

const LayoutContext = createContext<LayoutContextType | undefined>(undefined);

export const LayoutProvider = ({ children }: { children: ReactNode }) => {
    const [position, setPosition] = useState<LayoutPosition>('left');
    const [isRtl, setIsRtl] = useState<boolean>(false);

    useEffect(() => {
        let storedPosition: LayoutPosition | null = null;

        if (isDemoMode()) {
            storedPosition = getCookie('layoutPosition') as LayoutPosition;
        } else {
            const globalSettings = (window as any).page?.props?.globalSettings;
            storedPosition = globalSettings?.layoutDirection as LayoutPosition;
        }

        if (storedPosition === 'left' || storedPosition === 'right') {
            setPosition(storedPosition);
            setIsRtl(storedPosition === 'right');
        }

        // Always keep document direction as LTR to prevent sidebar issues
        document.documentElement.dir = 'ltr';
        document.documentElement.setAttribute('dir', 'ltr');

        const handleLanguageChange = (event: CustomEvent) => {
            const { direction } = event.detail;
            if (direction) {
                setPosition(direction);
                setIsRtl(direction === 'right');
            }
        };

        const handleLayoutDirectionChange = (event: CustomEvent) => {
            const { direction } = event.detail;
            if (direction) {
                setPosition(direction);
                setIsRtl(direction === 'right');
            }
        };

        window.addEventListener('languageChanged', handleLanguageChange as EventListener);
        window.addEventListener('layoutDirectionChanged', handleLayoutDirectionChange as EventListener);

        return () => {
            window.removeEventListener('languageChanged', handleLanguageChange as EventListener);
            window.removeEventListener('layoutDirectionChanged', handleLayoutDirectionChange as EventListener);
        };
    }, [(window as any).page?.props?.globalSettings]);

    const updatePosition = (val: LayoutPosition) => {
        setPosition(val);
        setIsRtl(val === 'right');
        
        // Always keep document direction as LTR to prevent sidebar issues
        document.documentElement.dir = 'ltr';
        document.documentElement.setAttribute('dir', 'ltr');
        
        // Save both cookies immediately when position is updated
        if (isDemoMode()) {
            setCookie('layoutPosition', val);
            setCookie('layoutDirection', val);
        }
    };

    const saveLayoutPosition = (positionParam?: LayoutPosition) => {
        const positionToSave = positionParam || position;
        
        if (isDemoMode()) {
            setCookie('layoutPosition', positionToSave);
            setCookie('layoutDirection', positionToSave);
        }
    };

    const effectivePosition: LayoutPosition = position;

    return <LayoutContext.Provider value={{ position, effectivePosition, updatePosition, saveLayoutPosition, isRtl }}>{children}</LayoutContext.Provider>;
};

export const useLayout = () => {
    const context = useContext(LayoutContext);
    if (!context) throw new Error('useLayout must be used within LayoutProvider');
    return context;
};