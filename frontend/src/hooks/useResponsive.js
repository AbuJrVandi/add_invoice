import { useEffect, useMemo, useState } from 'react';

function readWidth() {
  if (typeof window === 'undefined') {
    return 1280;
  }
  return window.innerWidth;
}

export default function useResponsive() {
  const [width, setWidth] = useState(readWidth);

  useEffect(() => {
    const onResize = () => setWidth(readWidth());
    window.addEventListener('resize', onResize);
    return () => window.removeEventListener('resize', onResize);
  }, []);

  return useMemo(() => {
    const isMobile = width >= 320 && width <= 639;
    const isTablet = width >= 640 && width <= 1023;
    const isDesktop = width >= 1024;

    return {
      width,
      isMobile,
      isTablet,
      isDesktop,
    };
  }, [width]);
}
