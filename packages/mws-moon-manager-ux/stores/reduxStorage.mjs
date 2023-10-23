// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com
import { writable } from 'svelte/store';
import * as _ from 'lodash';

export const state = writable({});

export const stateUpdate = (targetState, updateData) => {
  targetState.update((stateData) => _.merge(stateData, updateData));
}

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

export const offerTagsByKey = (stateData, key, _default = null) => {
  const keyToTag = stateGet(stateData, 'offerTagsByCatSlugAndSlug');
  if (!keyToTag || !(keyToTag[key] ?? false)) {
    console.debug(
      `MWS redux storage fail to found tag ${key} inside offerTagsByCatSlugAndSlug`,
      keyToTag,
      stateData,
    );
    return _default;
  }
  return keyToTag[key];
}

export const offerTagsByCatSlugAndSlug = (stateData, catSlug, slug, _default = null) => {
  const key = `${catSlug}|${slug}`;
  return offerTagsByKey(stateData, key, _default);
}