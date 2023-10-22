// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
import { writable } from 'svelte/store';

export const state = writable({});

export const stateGet = (stateData, propertyPath, _default = null) => {
  if (typeof propertyPath === 'string') {
    propertyPath = propertyPath.split('.');
  }
  // if (!Array.isArray(propertyPath) || !propertyPath.length) {
  if (!propertyPath || !propertyPath.length || !propertyPath.shift) {
    console.warn('WRONG MWS redux storage key : ', propertyPath);
    return _default;
  }

  const propertyKey = propertyPath.shift();
  // if (!stateData || !(stateData[slug] ?? false)) {
  // https://dmitripavlutin.com/check-if-object-has-property-javascript/
  if (!stateData || !stateData.hasOwnProperty(propertyKey)) {
    return _default;
  }
  const value = stateData[propertyKey];

  return propertyPath.length
  ? stateGet(value, propertyPath, _default)
  : value ;
}

export const slugToOfferTag = (stateData, slug, _default = null) => {
  const slugToTag = stateGet(stateData, 'slugToOfferTag');
  if (!slugToTag || !(slugToTag[slug] ?? false)) {
    console.debug(
      `MWS redux storage fail to found tag ${slug} inside slugToOfferTag`,
      slugToTag,
      stateData,
    );
    return _default;
  }
  return slugToTag[slug];
}